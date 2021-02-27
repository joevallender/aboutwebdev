---
extends: _layouts.post
section: content
title: "Trust Laravel Valet self-signed certificate for iOS development"
date: 2020-07-01
#description: Fast local search powered by FuseJS
#cover_image: /assets/img/post-cover-image-1.png
#categories: [laravel, ios, api]
---
I needed to test an app on the iOS simulator that used a local API provided by Laravel over Valet. I’d enabled ssl for the domain with the `valet secure` command but was receiving the following error in the Xcode console.

> File Transfer Error: The certificate for this server is invalid. You might be connecting to a server that is pretending to be which could put your confidential information at risk.

I had a real nightmare Googling a good solution, but finally came across exactly what I was looking from Juan Rangel on StackOverflow.
[https://stackoverflow.com/a/55098328/426171](https://stackoverflow.com/a/55098328/426171)

In the end, all that I needed to do was drag the following file and drop it on the emulator.

> ~/.config/valet/CA/LaravelValetCASelfSigned.pem

The option in the picture below was automatically enabled when I dropped the file. If you want to check, you can find it at Settings > General > About > Certificate Trust Settings (at the very bottom of the About screen).

![](/assets/img/certificate-trust.png)
