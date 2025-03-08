# SafeBytes

v. 0.0.1

## A Secure Data Management System

This project is designed to securely collect, store, and query sensitive information (e.g., bank details) 
while ensuring maximum privacy and security. Data is encrypted upon storage, and the server itself 
cannot decrypt the information as it does not have access to the necessary keys. 
The decryption keys are solely known to the data uploader (via CSV upload) and the end-user 
(via a secure email link combined with a unique 8-character two-factor authentication code). 
This ensures a **zero-knowledge** approach, where sensitive data is fully protected 
even from the server's perspective.

## Features

### Current Features:

- **Encryption and Decryption**: Encrypt and decrypt sensitive user data securely using AES-256-CBC.
- **Session Key Management**: Dynamically split keys for secure handling of encryption keys.
- **Web Form**: Collect user data, including personal and payment information, through a responsive web interface.

### Upcoming Features (Todos):

1. **CSV Import/Export**:
    - Import entries via CSV.
    - Export data (links) into a CSV format.
2. **Email Notifications**:
    - Send emails for new entries added to the system.
3. **Web Interface for Decryption**:
    - A dedicated webpage to decrypt stored data updates using the partial encryption key.
4. **Refactoring**:
    - Organize the codebase into `public` and `lib` folders to improve maintainability.

## How It Works

### Data Encryption and Decryption

The `crypt.php` file provides utilities:

- **Key Splitting**: A 56-character key is split into two components (`schluessel` and `masterKey`) for enhanced security.
- **Encryption**: Uses the `AES-256-CBC` method with a provided key and initialization vector (IV) derived from the key.
- **Decryption**: Uses the `masterKey` to decrypt stored data.

### Workflow

1. **Frontend (Web Form)**:
    - Handles user input for sensitive data.
    - Securely interacts with keys for encryption.
2. **Backend API**:
    - `update.php` accepts encrypted data and logs it into a database along with metadata (e.g., IP address, creation time).
    - Validates and responds to client requests with success/failure status.

### Data Handling

- **Database** structure example:

```mariadb
CREATE TABLE updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schluessel VARCHAR(255) NOT NULL,
    encryptedData TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL
);
```

- Stores encrypted data securely, along with metadata like IP address and timestamp.

## Setup Instructions

1. Clone the repository:

`git clone https://github.com/2Bits-XandA/SafeBytes.git`

1. Install dependencies:
    - PHP 7 or higher.
    - MySQL or compatible database.
2. Copy `config.php-dist` to  `config.php` and update with your database connection details.
3. Run the provided SQL script to set up the database:

```mariadb
CREATE TABLE updates (
   id INT AUTO_INCREMENT PRIMARY KEY,
   schluessel VARCHAR(255) NOT NULL,
   encryptedData TEXT NOT NULL,
   ip_address VARCHAR(45) NOT NULL,
   created_at DATETIME NOT NULL
);
```

## Upcoming Development Goals

- **CSV functionality**:
    - Add utilities for importing entries and exporting existing links to/from CSV files.
- **Email Integration**:
    - Notify users via email about newly added entries.
- **Key Decryption Web Tool**:
    - Provide a secure interface for partial key handling and decryption of encoded updates.
- **Refactored Directory Structure**:
    - Migrate shared libraries and core functionality into a distinct directory (`lib`) for better code organization.
    - create public folder to seperate accessible Files from backend functionality

## Security Considerations

1. **Encryption**:
    - All sensitive data is encrypted using AES-256-CBC before storage.
    - Key management uses a split mechanism (`schluessel` and `masterKey`).
2. **Input Validation**:
    - Frontend and backend validation are implemented to ensure data integrity and prevent malicious data submission.
3. **Session Management**:
    - Keys are safely stored in server-side sessions, mitigating exposure risks.

## Contributing

1. **Report Issues**: Open a GitHub issue for bug reports or feature requests.
2. **Pull Requests**:
    - Fork the project.
    - Make changes in a feature branch.
    - Submit a pull request for review.

## License

This project is licensed under the [MIT License](https://mit-license.org/).