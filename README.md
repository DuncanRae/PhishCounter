
# PhishCounter

A quick and simple Phishing Simulation for use in demos and Security Awareness Training (SAT) that only counts the number of people who enter credentials, but does not collect any other information.

I've struggled to find a quick and easy phishing simulation tool that just puts a page up that is easy to access for a simple QR code demo or a simple targeted simulation. Other tools like ZPhisher or BlackEye are simple to set up, but don't allow easy access to the pages. They also use CloudFlare tunnels or NGROK, both of which don't work very reliably due to protections they have in place to prevent them from being used for potentially malicious purposes. 

I built this one with the help of GPT 4o to simulate a simple page (either Google or Microsoft) that can be hosted on a small Linux server. 

I use a .xyz domain ($1 per year), DNS hosted at CloudFlare and a small Ubuntu instance at AWS. I have included a script that can update the DNS record at CloudFlare each time ther server starts so no permanent Elastic IP is required which would add cost. 

Point the "victims" to https://secure-login.YOURDOMAIN.zyx/index.html to see the login page. Once they enter credentials they are sent to the https://secure-login.YOURDOMAIN.zyx/thankyou.html page which tells them "Thanks, you are now entered into today's prize draw" but you can change this to whatever suits your pretence. 

If you do want to collect the email addresses of people who have filled in credentials, ensure you use the Google or Microsoft labeled index.html files with the save-email in the name. This will write all email addresses to a text file called emails.txt.

Access the https://secure-login.YOURDOMAIN.zyx/counter.html to see how many people have entered credentials (counted by how many times the ThankYou page has been loaded) or to reset the counter for the next demo. 

Please use this if it helps you educate your users around the dangers of phishing and Social Engineering. Obviously, only use it for education and never any malicious purposes. 



## Setup and Installation

This setup requires a Ubuntu Linux server to host the APache server and the pages needed. This tutorial won't cover the setup of the server itself, but assumes the server us up and running and had ports 80 and 443 exposed to the internet. The config all happens via SSH.

## Prerequisites

- A domain name pointed to your server.
- A server running Ubuntu.
- Basic knowledge of terminal commands.

## Steps

### 1. Update the System and Install Apache and Certbot

Update your system and install Apache and Certbot (the Let's Encrypt client).

```bash
sudo apt update
sudo apt install -y apache2 certbot python3-certbot-apache
```

### 2. Start and Enable Apache
Start the Apache service and enable it to start on boot.

```bash
sudo systemctl start apache2
sudo systemctl enable apache2
```

### 3. Enable PHP Module for Apache

First, you need to install PHP if it is not already installed. The exact package name can depend on your Linux distribution.

```bash
sudo apt update
```
Install PHP.
```bash
sudo apt install php libapache2-mod-php
```

### 4. Place the HTML and PHP files into the web directory

Go onto the /var/www/html/ direcroty and use "sudo nano filename.html" to create the files. Paste the contents of the files from this page into nano. Control-O saves, then Control-X quits. 

You can also download the files or clone the repo if that is easier for you. 

The index-google.html file is set up to look like the Google page. There is a index-microsoft.html file which you can use if you are working in a Microsoft environment. Choose the appropriate one and copy it's contents to index.html on the server.

Create the counter.txt file.
```bash
sudo touch counter.txt
```
Set the correct permission on the counter.txt file so the php script can update it (assuming the user is the default www-data user and group on Ubuntu).
```bash
sudo chown www-data:www-data counter.txt
sudo chmod 664 counter.txt
```
Create and set permissions on the email.txt file.
```bash
sudo touch /var/www/html/emails.txt
sudo chmod 666 /var/www/html/emails.txt
```

### 5. Configure Apache to Serve Your PHP Site

Create a new Apache configuration file for your site. Replace your_domain with your actual domain name.

```bash
sudo nano /etc/apache2/sites-available/your_domain.conf
```
Add the following content:

```apache
<VirtualHost *:80>
    ServerName your_domain.xyz
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```
Enable the site configuration and the rewrite module.

```bash
sudo a2ensite your_domain.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```
### 6. Create the public DNS record

Log into your DNS provider and create an A record for the URL you're going to use. eg. secure-logon-page.12345678.xyz pointing to the public IP address of your instance. 

### 7. Obtain an SSL Certificate from Let’s Encrypt

Run Certbot to obtain and install an SSL certificate. Replace your_domain with your actual domain name.

```bash
sudo certbot --apache -d host.your_domain
```
Follow the prompts to complete the certificate installation. Certbot will automatically configure Apache to use the SSL certificate.

After Certbot finishes, your site should be accessible via HTTPS. Open your web browser and go to https://host.your_domain. You should see the PHP page served securely over HTTPS.

### 8. Auto-renewal of Certificates

Certbot takes care of auto-renewal, but it’s a good idea to test it.

```bash
sudo certbot renew --dry-run
```

## (Optional) Automatic DNS Record Update

This section enables the server to automatically update the public DNS record at CloudFlare each time the server starts in case it gets a new public IP address.

### 9. Get CloudFlare API Token

	1.	Log in to your CloudFlare account.
	2.	Navigate to “My Profile” -> “API Tokens”.
	3.	Create a new token with “Edit DNS Zone” permissions for the relevant zone.

You'll need the Zone ID this zone. You can find the Zone ID when you create the API key.

### 10. Install Required Packages

Install the necessary packages to make HTTP requests and handle JSON data.

```bash
sudo apt update
sudo apt install curl jq
```

### 11. Create the Update Script

Create a script that will retrieve the server’s public IP address and update the DNS A record at CloudFlare.

```bash
sudo nano update_dns.sh
```
Paste the following into the Nano window and update it with your DNS Zone and API token:

```sh
#!/bin/bash

# CloudFlare configuration
AUTH_KEY="YOURAPIKEY"
ZONE_ID="YOURZONEID"
RECORD_NAME="YOURDNSNAME.12345678.xyz"
RECORD_TYPE="A"

# Get the current public IP address
IP=$(curl -s http://ipv4.icanhazip.com)

# Get the DNS record ID
RECORD_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?type=$RECORD_TYPE&name=$RECORD_NAME" \
     -H "Authorization: Bearer $AUTH_KEY" \
     -H "Content-Type: application/json" | jq -r '.result[0].id')

# Update the DNS record
curl -s -X PUT "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records/$RECORD_ID" \
     -H "Authorization: Bearer $AUTH_KEY" \
     -H "Content-Type: application/json" \
     --data "{\"type\":\"$RECORD_TYPE\",\"name\":\"$RECORD_NAME\",\"content\":\"$IP\"}"
```
Press Control-O to save, then Control-X to quit.

### 12. Make the script executable

```bash
sudo chmod +x update_dns.sh
```
### 13. Run the Script at Startup

You can configure the script to run at startup using systemd.

Create a systemd service file:

```bash
sudo nano /etc/systemd/system/update-dns.service
```
Add the following content to the service file:

```ini
[Unit]
Description=Update CloudFlare DNS record with public IP address

[Service]
ExecStart=/var/www/html/update_dns.sh

[Install]
WantedBy=multi-user.target
```
Control-O to save, Control-X to quit.

Reload the systemd daemon to recognize the new service:
```bash
sudo systemctl daemon-reload
```
Enable the service to run at startup:
```bash
sudo systemctl enable update-dns.service
```
Start the service:
```bash
sudo systemctl start update-dns.service
```
This setup will ensure that your server updates the DNS A record with its public IP address each time it starts up.
