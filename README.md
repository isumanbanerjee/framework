# Lightweight PHP Framework by Suman Banerjee

A lightweight and modular PHP framework designed for simplicity, performance, and flexibility. This framework is perfect for developers who value clean, concise, and efficient code, enabling the creation of scalable and secure web applications without the overhead of larger frameworks.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Autoloading](#autoloading)
- [Included Dependencies](#included-dependencies)
- [Directory Structure](#directory-structure)
- [Contributing](#contributing)
- [License](#license)
- [Author](#author)

---

## Features

- **Lightweight & Modular**: Use only the components you need.
- **Performance-Oriented**: Optimized for speed and minimal resource usage.
- **Built with PHP 8.4**: Leverages modern PHP features and best practices.
- **Extensive Third-Party Integrations**: Pre-configured with popular libraries.
- **Customizable**: Easily extend functionality and adapt it to your needs.
- **Secure**: Includes protection against common vulnerabilities such as XSS, CSRF, and SQL injection.

---

## Requirements

- **PHP 8.4 or higher**
- Required PHP extensions:
    - `curl`
    - `fileinfo`
    - `openssl`
    - `pdo`
- Composer

---

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/your-repository.git
   ```

2. Navigate to the project directory:
   ```bash
   cd your-repository
   ```

3. Install dependencies using Composer:
   ```bash
   composer install
   ```

---

## Usage

### Getting Started

1. Configure your environment variables in `.env` (if applicable).
2. Use the pre-configured `Core`, `System`, and `Configuration` namespaces to structure your application.
3. Leverage built-in tools like routing, database handling, and templating to kickstart your project.

---

## Autoloading

This framework uses PSR-4 autoloading for easy management of namespaces and files. Below is the namespace-to-directory mapping:

| Namespace                        | Directory                 |
|----------------------------------|---------------------------|
| `Configuration\`                 | `Configuration/`          |
| `Core\`                          | `Core/`                   |
| `Core\Model\`                    | `Core/Model/`             |
| `Core\View\`                     | `Core/View/`              |
| `Core\Controller\`               | `Core/Controller/`        |
| `System\Model\`                  | `System/Model/`           |
| `System\View\`                   | `System/View/`            |
| `System\Controller\`             | `System/Controller/`      |

---

### Namespace Overview

- **Core**: Contains the core functionality of the framework that is fundamental and immutable. These classes should not be altered as they provide the essential building blocks of the framework.
- **System**: Includes classes that inherit and extend functionality from the Core namespace, allowing customization and project-specific logic while maintaining the integrity of core functionalities.| Namespace                        | Directory                  |
  |----------------------------------|---------------------------|
  | `Configuration\`                 | `Configuration/`          |
  | `Core\`                          | `Core/`                   |
  | `Core\Model\`                    | `Core/Model/`             |
  | `Core\View\`                     | `Core/View/`              |
  | `Core\Controller\`               | `Core/Controller/`        |
  | `System\Model\`                  | `System/Model/`           |
  | `System\View\`                   | `System/View/`            |
  | `System\Controller\`             | `System/Controller/`      |

---

## Included Dependencies

The framework comes bundled with several popular libraries to enhance functionality:

- **Bootstrap (v5.3.2)**: Responsive frontend framework.
- **Bootstrap Icons (v1.10.5)**: Icon set for web development.
- **jQuery (v3.4.1)**: JavaScript library for DOM manipulation.
- **PHPMailer (v6.8.0)**: Email sending library.
- **Chart.js (v4.3.3)**: Interactive charts and graphs.
- **Ramsey/UUID (v4.7.4)**: Universally unique identifier library.
- **MatthiasMullie/Minify (v1.3.70)**: CSS and JavaScript minification.
- **Voku/HTML-Min (v4.5.0)**: HTML minification.
- **Animate.css (v3.5.3)**: Animation library.
- **Melbahja/SEO (v2.1.1)**: SEO optimization tools.

---

## Directory Structure

```plaintext
├── Configuration/
├── Core/
│   ├── Controller/
│   ├── Model/
│   └── View/
├── System/
│   ├── Controller/
│   ├── Model/
│   └── View/
├── resources/
│   ├── vendor/
│   ├── npm/
│   └── bower/
├── composer.json
└── index.php
```

---

## Contributing

Contributions are welcome! Please fork this repository, create a feature branch, and submit a pull request. Ensure all changes are thoroughly tested and documented.

---

## License

This framework is licensed under the GPL-3.0-or-later License. See the [LICENSE](LICENSE) file for details.

---

## Author

**Suman Banerjee**
- Email: [contact@isumanbanerjee.com](mailto:contact@isumanbanerjee.com)
- GitHub: [isumanbanerjee](https://github.com/isumanbanerjee)

---

Happy coding! 🎉
