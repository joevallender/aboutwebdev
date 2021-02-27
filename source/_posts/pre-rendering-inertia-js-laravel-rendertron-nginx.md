---
extends: _layouts.post
section: content
title: "Pre-rendering Inertia.js for SEO using Laravel, Rendertron & Nginx"
date: 2020-03-27
#description: Fast local search powered by FuseJS
#cover_image: /assets/img/post-cover-image-1.png
#categories: [laravel, nginx, inertia.js, seo, rendertron]
---
I’m going to run through this using the same kit I’ve used to set it up. Laravel, Inertia.js, Vue, Rendertron, Nginx, Laravel Forge, Ubuntu.

It might sound like quite a specific set of kit, but a lot of it applies to any Javascript app running on Nginx. So perhaps others might find it useful too.

**Install the Ping CRM demo app**

This isn’t the best example of the type of app you’re likely to be concerned about the SEO of, however if you’re working with Inertia.js it will be a familiar project to use.

Add the site pingcrm.mydomain.com to forge using the repo 
[https://github.com/inertiajs/pingcrm/](https://github.com/inertiajs/pingcrm/)

Access the server from a terminal and run these commands to setup Ping CRM.

    cd ~/pingcrm.mydomain.com
    npm ci # or npm i
    npm run dev
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite

Deploy the site using forge and optionally return to the terminal to seed the database.

Visit pingcrm.mydomain.com and check you can see the site, it will be showing the login page. View the source or inspect the code to see that it’s *before* state, an app container div and data which is rendered in the browser.

Set APP_DEBUG=false in your .env file to disable laravel-debugbar so we aren’t unnecessarily rendering it’s Javascript.

**Install Rendertron**

[https://github.com/GoogleChrome/rendertron](https://github.com/GoogleChrome/rendertron)

Rendertron suits this purpose perfectly, and unless you have some very custom requirements it’s going to be better than rolling your own Express-wrapped Puppeteer code.

On the same web server run these commands

    cd ~/
    git clone https://github.com/GoogleChrome/rendertron.git
    cd rendertron
    npm install
    npm run build
    npm run start

If you get any errors about missing libraries, run this to make sure you have all the Puppeteer dependencies.

    sudo apt-get install gconf-service libasound2 libatk1.0–0 libatk-bridge2.0–0 libc6 libcairo2 libcups2 libdbus-1–3 libexpat1 libfontconfig1 libgcc1 libgconf-2–4 libgdk-pixbuf2.0–0 libglib2.0–0 libgtk-3–0 libnspr4 libpango-1.0–0 libpangocairo-1.0–0 libstdc++6 libx11–6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget

These are for Debian as per the Puppeteer documentation, which also provides a list for CentOS: [https://github.com/puppeteer/puppeteer/blob/master/docs/troubleshooting.md#chrome-headless-doesnt-launch-on-unix](https://github.com/puppeteer/puppeteer/blob/master/docs/troubleshooting.md#chrome-headless-doesnt-launch-on-unix)

Leave **npm run start** running and open another terminal on the same server.

Check that Rendertron is able to reach Ping CRM

```bash
wget http://localhost:3000/render/http://pingcrm.mydomain.com
```

**Nginx changes**

There are of course various ways to intercept crawlers. And although perhaps a more *Laravel* way might be to use a composer package providing middleware, using Nginx gives us some more transferrable knowledge.

Open the Nginx config and change this part

    location / {
      try_files $uri $uri/ /index.php?$query_string;
    }

To

    location / {
     
        set $prerender 0;

        if ($http_user_agent ~* “googlebot|yahoo|bingbot”) {
        set $prerender 1;
        }

        if ($args ~ “_escaped_fragment_|prerender=1”) {
        set $prerender 1;
        }
        
        if ($http_user_agent ~ “Prerender”) {
        set $prerender 0;
        }

        if ($uri ~* “\.(js|css|xml|less|png|jpg|jpeg|gif|pdf)”) {
        set $prerender 0;
        }

        if ($prerender = 1) { 
        rewrite .* /render/$scheme://$host$request_uri break;
        proxy_pass [http://localhost:3000](http://localhost:3000);
        
        }
     
        try_files $uri $uri/ /index.php?$query_string;
    }

I’ve reduced the user agents and file types for readability above, my actual config has:

    js|css|xml|less|png|jpg|jpeg|gif|pdf|doc|txt|ico|rss|zip|mp3|rar|exe|wmv|doc|avi|ppt|mpg|mpeg|tif|wav|mov|psd|ai|xls|mp4|m4a|swf|dat|dmg|iso|flv|m4v|torrent|ttf|woff|svg|eot

And

    googlebot|yahoo|bingbot|baiduspider|yandex|yeti|yodaobot|gigabot|ia_archiver|facebookexternalhit|twitterbot|developers\.google\.com

The config is fairly self explanatory. Default to no pre-rendering unless we’re being crawled, with the exception of asset files. Then either refer to our local proxy passing the current URL, or **try_files** as normal

**Give it a spin**

Visit pingcrm.mydomain.com again, and you should still see the original page.

Now use your preferred Chrome extension to change your User-Agent to googlebot, visit pingcrm.mydomain.com and you should see the pre-rendered page.

View the source and notice how the Javascript tags have been removed to prevent a crawler trying to run the code for a second time and getting an error.

**Going further**

In production you’d need to enable a cache. And ideally prime that cache using a script that calls popular URLs on Rendertron as part of the deploy process, and perhaps using content changed events to populate the cache for things like articles.

You’ll also want to use Forge to move Rendertron’s start command into a daemon so it’s run and monitored by Supervisor.