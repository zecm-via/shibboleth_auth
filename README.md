Shibboleth Authentication for TYPO3 CMS
=======================================
EXT:shibboleth_auth

Fork of the TYPO3 extension shibboleth_auth to fix TYPO3 CMS 6.0+ compatibility issues and shipping with an Extbase-based Login plugin.

This extension enables the single sign-on based on Shibboleth for frontend and backend authentication. 

Prerequisites
-------------
Apache's Shibboleth module has to be installed on the web server before you can use this extension.

The following .htaccess rules must be added to the .htaccess file in document root:

    AuthType Shibboleth
    Require Shibboleth

And if you use the RealUrl extension, the following must be added to .htaccess file, too:

    <IfModule mod_shib>
    	RewriteRule ^(Shibhandler.*)/ - [L]
    	RewriteRule ^(Shibboleth.sso)/ - [L]
    </IfModule>

It must be the first RewriteRule in htaccess.

After installing the extension with TYPO3's extension manager you have to activate the basic features in the extension's settings. By default, the extension will not do anything. Shibboleth authentication can be activated for frontend and backend separately. It is highly recommended to keep a browser window with a logged in admin user open before activating and testing the backend login (see below). Otherwise you could lock yourself out, if your Shibboleth configuration is incorrect.

Using the Frontend login
------------------------
For frontend login you need a TYPO3 folder to store the users. To activate the frontend authentication a plugin is available to the editors. For the plugin to work, the static TypoScript needs to be included. You can use your own templates by just overriding the paths via TypoScript constants:

    plugin.tx_shibbolethauth {
	    view {
		    templateRootPath = EXT:shibboleth_auth/Resources/Private/Templates/
		    partialRootPath = EXT:shibboleth_auth/Resources/Private/Partials/
		    layoutRootPath = EXT:shibboleth_auth/Resources/Private/Layouts/
	    }
    }

If no frontend user is logged in, a login link is displayed. *This is a difference to the original shibboleth_auth extension that performs an automatic redirect if no user is logged in.*

Just like other logins in TYPO3 the plugin itself provides the login, not the protection. If you want the access to the content of your login page restricted, you have to assign the related group in the access settings. Do not assign any restrictions to the Shibboleth plugin itself! Otherwise no login redirects will be made.

In the plugin, you can also configure the redirect URL. If a parameter `redirect_url` is passed in the URL to the login page, this URL is used for a redirect on successful authentication.

Using the Backend login
-----------------------
*TODO: Rework*

For security reasons backend users are not imported automatically. So before you start to activate the backend login, go to the root folder of your TYPO3 installation, create a user with the username of an existing Shibboleth account and give it admin rights. Go back to the extension manager, open the settings of the Shibboleth extension and activate the backend login and the option “Only Shibboleth Backend”. Do not close the window! Open another browser (for example Firefox and Chrome as shown in the screen shot) and try to login with the former created Shibboleth user.

After successful login you can close the other browser. Having Shibboleth's backend login enabled as described above you will never be able to login with a normal TYPO3 account again. Insecure extensions which allow SQL injection could not be misused to read the password hash of one of your admin users, because these passwords will never work again. Therefore Shibboleth authentication brings an increase of backend security against hacker attacks.

The extension should be able to handle session timeouts. Therefore the configuration variable showRefreshLoginPopup is set to open the login form in a new window. Keep in mind that TYPO3's refresh login does not work in debug mode. As the life time of your login depends on the Shibboleth sever variable it is recommended to set TYPO3's session timeout to a higher value if problems with the refresh login occur.

How authentication works
------------------------
Shibboleth is a single sign-on technology, that allows you to access the protected content of one or more service providers by logging in at an identity provider. This is implemented via redirects and server variables provided by the web server's Shibboleth module. When the user tries to access protected content, TYPO3 looks if there is an existing fe-user or special evironment variables with the user's data. If not, it redirects to a directory (= ShibHandler) protected by the Apache's Shibboleth module. This will send the browser to the assigned identity provider with the login page. After logging in the browser gets redirected to the TYPO3 page again, where the php script can now read the server variables with the user's data. The authentication and the environment variables are not provided by this extension! TYPO3's Shibboleth authentication needs an installed shibboleth module on your web server!
