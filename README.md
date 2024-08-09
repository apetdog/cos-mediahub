# COS MediaHub

![COS MediaHub Logo](./logo.png)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://www.php.net/)
[![Composer](https://img.shields.io/badge/Composer-2.x-orange.svg)](https://getcomposer.org/)

COS MediaHub 是一个强大而简洁的工具，为开发者提供了一站式解决方案，用于将图片无缝上传到腾讯云对象存储（COS）服务。

## 🚀 特性

- 简单易用的 API 接口
- 高效的图片上传和管理
- 与腾讯云 COS 无缝集成
- 灵活的配置选项
- 支持大文件上传

## 🛠️ 安装

1. 克隆仓库：

   ```bash
   git clone https://github.com/your-username/cos-mediahub.git
   cd cos-mediahub
   ```

2. 安装依赖：

   ```bash
   composer install
   ```

3. 配置环境变量：

   复制 `.env.example` 文件并重命名为 `.env`，然后根据您的腾讯云 COS 账户信息填写相关参数。

4. 配置 Web 服务器：

   如果使用 Nginx，请在配置文件中添加以下内容以支持大文件上传：

   ```nginx
   client_max_body_size 10M;
   ```

   根据您的需求，可以调整上传文件大小限制。

## 🖥️ 使用方法

// TODO: 添加基本的使用示例和 API 文档链接

## 🤝 贡献

我们欢迎并感谢所有形式的贡献！如果您想为 COS MediaHub 做出贡献，请查看我们的 [贡献指南](CONTRIBUTING.md)。

## 📄 许可证

COS MediaHub 采用 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。

## 📞 支持

如果您在使用过程中遇到任何问题或有任何建议，请 [创建一个 issue](https://github.com/your-username/cos-mediahub/issues) 或联系我们的支持团队。

---

<p align="center">
  用 ❤️ 制作 by <a href="https://apetdog.github.io">Apetdog Inc</a>
</p>
