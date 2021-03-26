---
extends: _layouts.post
section: content
title: Fix SignatureDoesNotMatch error on S3 direct upload
date: 2021-03-15
---

AWS S3* presigned URLs are great for uploading user files to a bucket without it having to pass through your server. 

_* Or any S3 compatible API, in my case I was using Linode Object Storage_

I'd already built various projects using this approach so I was stumped recently when I got a SignatureDoesNotMatch error. I was using almost exactly the same code working in production on other projects. The only difference this time is that I needed to set the uploads to be publically accessible rather than the default of private. I added the ACL of `public-read` to my code and got the error:

    <?xml version="1.0" encoding="UTF-8"?>
    <Error><Code>SignatureDoesNotMatch</Code>
    <Message>The request signature we calculated does not match the signature you provided. Check your key and signing method.</Message>


A simplified version of the controller generating the presigned URL is below. I was using Laravel and accessing the underlying S3 client with `$s3->getDriver()->getAdapter()->getClient()` so anything derrived from `$client` will work in another PHP project using the S3 SDK.

    $s3 = Storage::disk('s3');
    $client = $s3->getDriver()->getAdapter()->getClient();

    $command = $client->getCommand('PutObject', [
        'Bucket' => Config::get('filesystems.disks.s3.bucket'),
        'Key'    => time(),
        'ACL'    => 'public-read'
    ]);

    $request = $client->createPresignedRequest($command, "+20 minutes");
    return (string) $request->getUri();

On NodeJS it would look something like:

    const params = {
        Bucket: 'bucket-name',
        Key: 'file-name',
        Expires: 1200
        // ...
        ACL: 'public-read'
    };
    let signedUrlPut = s3.getSignedUrl('putObject', params);

I'm sure you can see the pattern for whatever back end framework or code you're using.

On the front end you call your server to get the presigned URL:

    let response = await axios.post('/get-presigned-url')

And then use that to send the file to S3:

    let file = document.getElementById('fileUpload').files[0]
    axios.put(response.data, file);

This is where the SignatureDoesNotMatch error occurs. It's a bit confusing at first because all the params I was using in previous projects were configured in the back end `$client->getCommand('PutObject', $params)` but if specifying an ACL you need to do it on both the back and front end. 

The solution was to add the equivalent ACL header to the upload request as you can see here:

    const options = {
        headers: {
            'x-amz-acl': 'public-read'
        },
    };

    axios.put(response.data, file, options);

