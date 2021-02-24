# digitalriver_drpay
1. Go to https://github.com/DiconiumUS/digitalriver
and download the Extension.
2. Extract the contents of the ZIP file on your computer.
3. Connect your website source folder with the FTP/SFTP/SSH client and upload the DrPay folder inside the app/code/Digitalriver folder from the extension package to the corresponding root folder of your Magento installation.

Note: You need to create the path "app/code/Digitalriver" manually if not created already.

4. Connect to your Magento directory with Secure Shell (SSH) and run the following two commands:

php bin/magento module:enable Digitalriver_DrPay

php bin/magento setup:upgrade

5. Sign in to the Magento backend system and then click System.
6. Go the Cache Management page and click the Flush Magento Cache button. When this action completes, the connector is installed.
