<?php
require 'vendor/autoload.php';
$config = require 'config.php';

use Qcloud\Cos\Client;

// 设置跨域头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");


// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit(0);
}

// COS 配置
$cosClient = new Client([
  'region' => $config['region'],
  'schema' => 'https',
  'credentials' => [
    'secretId' => $config['secretId'],
    'secretKey' => $config['secretKey']
  ]
]);

// 路由处理
$route = $_GET['route'] ?? '';
switch ($route) {
  case 'upload':
    handleUpload($cosClient, $config);
    break;
  case 'list':
    handleList($cosClient, $config);
    break;
  case 'getFile':
    handleGetFile($cosClient, $config);
    break;
  default:
    echo json_encode(['error' => '无效的路由']);
    break;
}

// 上传到 COS
function handleUpload($cosClient, $config)
{
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否有文件上传且没有错误
    $file = $_FILES['image'] ?? $_FILES['file'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
      $fileName = $file['name'];
      $fileTmpPath = $file['tmp_name'];
      $fileSize = $file['size'];

      // 获取自定义目录
      $directory = $_GET['directory'] ?? $config['uploadDir'];
      $directory = rtrim($directory, '/') . '/';

      // 生成文件在 COS 中的路径
      $key = $directory . $fileName;

      // 检查文件大小（例如，限制为 10MB）
      $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
      if ($fileSize > $maxFileSize) {
        http_response_code(413);
        echo json_encode([
          'success' => false,
          'message' => '文件大小超过 10MB 的限制',
        ]);
        return;
      }

      try {
        // 检查目录是否存在，如果不存在则创建
        $dirKey = rtrim($config['uploadDir'], '/') . '/';
        $doesDirExist = $cosClient->doesObjectExist($config['bucket'], $dirKey);
        if (!$doesDirExist) {
          $cosClient->putObject([
            'Bucket' => $config['bucket'],
            'Key' => $dirKey,
            'Body' => '',
          ]);
        }

        // 上传文件到 COS
        $cosClient->putObject([
          'Bucket' => $config['bucket'],
          'Key' => $key,
          'Body' => fopen($fileTmpPath, 'rb'),
        ]);

        // 获取文件的 URL
        $url = $cosClient->getObjectUrl($config['bucket'], $key);

        // 返回成功的 JSON 响应
        echo json_encode([
          'success' => true,
          'message' => '文件上传成功',
          'url' => $url,
          'fileName' => $fileName,
        ]);
      } catch (Exception $e) {
        // 返回错误的 JSON 响应
        http_response_code(500);
        echo json_encode([
          'success' => false,
          'message' => '上传失败: ' . $e->getMessage(),
        ]);
      }
    } else {
      // 返回错误的 JSON 响应
      http_response_code(400);
      echo json_encode([
        'success' => false,
        'message' => '没有文件上传或上传错误',
      ]);
    }
  } else {
    // 返回错误的 JSON 响应
    http_response_code(405);
    echo json_encode([
      'success' => false,
      'message' => '方法不允许',
    ]);
  }
}

function handleList($cosClient, $config)
{
  try {
    // 获取分页参数
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 10;

    // 确保页码和每页数量为正整数
    $page = max(1, $page);
    $pageSize = max(1, min(100, $pageSize)); // 限制每页最大数量为100

    // 列出指定存储桶中的对象
    $result = $cosClient->listObjects([
      'Bucket' => $config['bucket'],
      'Prefix' => $config['uploadDir'],
      'MaxKeys' => 1000, // 设置一个较大的值以获取所有文件
    ]);

    $files = [];
    // 检查 Contents 是否存在且为数组
    if (isset($result['Contents']) && is_array($result['Contents'])) {
      // 遍历结果中的每个对象
      foreach ($result['Contents'] as $content) {
        // 过滤掉目录（通常目录的大小为0且键以'/'结尾）
        if ($content['Size'] > 0 && !str_ends_with($content['Key'], '/')) {
          $url = $cosClient->getObjectUrl($config['bucket'], $content['Key']);
          // 移除 URL 中的签名参数
          $url = strtok($url, '?');

          $files[] = [
            'key' => $content['Key'], // 对象的键
            'size' => $content['Size'], // 对象的大小
            'lastModified' => $content['LastModified'], // 对象的最后修改时间
            'url' => $url, // 对象的 URL
          ];
        }
      }
    }

    // 按最后修改时间降序排序
    usort($files, function ($a, $b) {
      return strtotime($b['lastModified']) - strtotime($a['lastModified']);
    });

    // 计算总页数
    $totalFiles = count($files);
    $totalPages = ceil($totalFiles / $pageSize);

    // 获取当前页的文件
    $offset = ($page - 1) * $pageSize;
    $currentPageFiles = array_slice($files, $offset, $pageSize);

    // 返回成功的 JSON 响应
    echo json_encode([
      'success' => true,
      'files' => $currentPageFiles,
      'pagination' => [
        'currentPage' => $page,
        'pageSize' => $pageSize,
        'totalPages' => $totalPages,
        'totalFiles' => $totalFiles,
      ],
    ]);
  } catch (Exception $e) {
    // 返回错误的 JSON 响应
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => '文件列表获取失败: ' . $e->getMessage(),
    ]);
  }
}

function handleGetFile($cosClient, $config)
{
  if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['key'])) {
    $key = $_GET['key'];

    try {
      // 获取对象的 URL
      $url = $cosClient->getObjectUrl($config['bucket'], $key);

      // 返回成功的 JSON 响应
      echo json_encode([
        'success' => true,
        'url' => $url,
      ]);
    } catch (Exception $e) {
      // 返回错误的 JSON 响应
      http_response_code(500);
      echo json_encode([
        'success' => false,
        'message' => '获取文件失败: ' . $e->getMessage(),
      ]);
    }
  } else {
    // 返回错误的 JSON 响应
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => '请求方法无效或缺少 key 参数',
    ]);
  }
}