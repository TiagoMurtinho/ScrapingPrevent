# ScrapingPrevent
**Web Application Firewall**

**ScrapingPrevent** is a web application firewall developed in PHP, designed to protect against malicious scrapers and automated attacks. It offers a range of security features such as IP checks, CAPTCHA validation, rate limiting, User-Agent validation, and more. The application allows configuring these features and applying custom punitive measures based on a point system.

## Features

- **AWS IP Check:** Checks if a user's IP is part of the Amazon Web Services (AWS) IP list.
- **Blacklisted IP Check:** Integrates with the [AbuseIPDB](https://www.abuseipdb.com/) API to verify if a user's IP is listed as malicious.
- **Captcha Validation:** Adds CAPTCHA to ensure traffic is generated by humans.
- **Honeypots:** Uses honeypots to trap bots attempting to access protected areas.
- **Rate Limiting (Ratelimiter):** Enforces limits on the number of requests an IP can make within a specific time window.
- **Referer Check:** Checks the referer header to ensure requests come from legitimate sources.
- **User-Agent Validation:** Inspects the User-Agent of requests to identify common bots.
- **Point System:** Assigns points to IPs that violate defined security parameters. Users can customize how many points are assigned.
- **Sanctions System:** When an IP reaches a configurable number of points, sanctions are applied, such as:
  - **Sleep:** Puts the IP on hold for a configurable period of time.
  - **Error:** Displays a custom error page.
  - **Block:** Blocks the IP's access.
- **Cookie ID:** When a user accesses the system for the first time, a **cookie** is sent with a unique **UUID**. If the **cookie** is absent in subsequent requests, a sanction is applied starting from the **third request** without the cookie, and the count is reset.
- **AWS IP Redirection:** If a user with an AWS IP exceeds the request limit (rate limit) **twice**, all IPs in the same IP range (subnet) will be redirected to the same **error page**.
  - **Error Page Display:** When IPs are redirected to the error page, clicking the **Back to Home Page** button will only unlock the IP that clicked, while the other IPs in the same range will remain redirected to the error page until their sanction conditions are fulfilled.

## Installation

### Prerequisites

- **PHP 7.x or higher**
- **Composer** (PHP dependency manager)
- **Web Server** (Apache, Nginx, etc.)
- **MySQL Database**

### Installation Steps

1. **Clone the repository**
    ```bash
    git clone https://github.com/your-username/ScrapingPrevent.git
    cd ScrapingPrevent
    ```

2. **Install dependencies with Composer**
    ```bash
    composer install
    ```

3. **Configure the `db_connection.php` file to connect to your database**
    - Open the `db_connection.php` file and add the correct database information (host, user, password, database name).
    - Example:
      ```php
      <?php
      $host = 'localhost';
      $user = 'root';
      $password = '';
      $dbname = 'scraping_prevent';

      try {
          $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (PDOException $e) {
          echo "Connection failed: " . $e->getMessage();
      }
      ?>
      ```

4. **Set up the database**

    - **Create the database**:
       The user should create a database in MySQL. The application will automatically create all the necessary tables the first time it is accessed. To create the database, run the following command in MySQL:
       
       ```sql
       CREATE DATABASE ScrapingPrevent;
       ```

    - **Database permissions**:
       Ensure that the MySQL user has the necessary permissions to access the database and create tables. The application will use the credentials configured in the `db_connection.php` file to connect to the database and create the tables automatically.

    - **Initial execution**:
       After creating the database, access the application for the first time. The application will check if the tables exist and, if not, it will create them automatically.

5. **Access your local or production server**

    - Configure your web server (e.g., Apache or Nginx) to serve the project from the directory where the code was cloned.
    - Ensure that the web server has permission to read and write in the project directory.
    - Once the server is configured, access the application in your browser to complete the setup. The first time the application is accessed, it will create the necessary database tables automatically.

## Configuration

### Configuration Interface

**ScrapingPrevent** provides an admin interface where you can enable or disable firewall features, such as IP checks, honeypots, CAPTCHAs, and more.

1. Go to the **Settings** page in the admin panel:
    - Here, you can enable or disable the security features of your choice.

2. **Point System Configuration:**
    - You can configure how many points an IP will accumulate for violating rules.
    - It's also possible to define when to apply sanctions, such as **sleep** time, showing an **error view**, or **blocking** the IP.

### Cookie ID System

- **First Access:** When a user accesses the system for the first time, a **cookie** with a unique UUID is sent to the browser.
- **Cookie Check:** For subsequent requests, the system checks for the presence of the cookie. If the **cookie** is missing, from the **third request** without the cookie, the system applies a sanction (e.g., **sleep**, error, or block) and resets the request count.

### AWS IP Redirection

- **AWS IP Check:** When a user with an AWS IP exceeds the request limit (rate limit) twice, all other IPs in the same AWS IP range will be redirected to the **error page**.
- **Error View Display:**
  - On the error view, there is a **Back to Home Page** button.
  - When the user clicks this button, only the IP that clicked will be unlocked, allowing access to the homepage again.
  - Other IPs in the same IP range will remain redirected to the **error page** until their sanction conditions are fulfilled.

### Example of Point System Configuration

- **Points:** When an IP reaches 10 points, a sanction is applied.
- **Sanction:** Sleep for 30 seconds.

### Custom Error View

You can configure a custom **error view** to be displayed when an IP reaches the defined point threshold.

### Example of Sanction System Configuration

- When an IP reaches the configured point threshold, one of the following sanctions can be applied:
  - **Sleep:** The IP will be on hold for a configurable number of seconds.
  - **Error:** The IP will see a custom error page.
  - **Block:** The IP will be permanently blocked.

## Contributing

Contributions are welcome! If you want to help improve **ScrapingPrevent**, follow these steps:

1. Fork this repository.
2. Create a branch for your modifications (`git checkout -b feature/new-feature`).
3. Make the necessary changes and commit them (`git commit -am 'Adding new feature'`).
4. Push to the remote repository (`git push origin feature/new-feature`).
5. Open a pull request.

## License

This project is licensed under the [MIT License](LICENSE).

## Contact

If you have any questions or suggestions, feel free to reach out:

- **Name:** Tiago Murtinho
- **Email:** tiago_miguelmurtinho@hotmail.com
- **LinkedIn:** [Tiago Murtinho](https://www.linkedin.com/in/tiago-murtinho/)

---

**ScrapingPrevent** was created to make the web safer by preventing malicious automated traffic and scraping attacks. Thank you for your contribution!
