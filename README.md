tree-hollow
===========

Secure message

When sending e-mails, the server providers are technically able to read them. The simple solution for that would be to use e-mail encryption. However, most people find this simple thing too complicated, so I decided to create a web-service
where one can post a message, storing it in an encrypted form for a while and noticing user about it via regular e-mail.
If password to message was provided, user will be able to open and read the message and eventually reply on it through a
simple link sent them. The e-mail provider won't have access to the message itself. Users can delete the message after they read it, making it imposible to give out to authorities even in case thay managed to intercept the password (otherwise they'll just get the encrypted blob). The possibility to deploy your own server makes it even harder for authorities to conduct massive interception.

IMPORTANT NOTICE
Currently the project is under (more or less) active development and IS NOT INTENDED TO BE USED YET. There are some several security flaws I know about and keep working on. I take no responsibility whatsoever for inappropriate use.
Wait for some stable release.
