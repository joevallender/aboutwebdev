<?php

// @var $container \Illuminate\Container\Container
// @var $events \TightenCo\Jigsaw\Events\EventBus

/*
 * You can run custom code at different stages of the build process by
 * listening to the 'beforeBuild', 'afterCollections', and 'afterBuild' events.
 *
 * For example:
 *
 * $events->beforeBuild(function (Jigsaw $jigsaw) {
 *     // Your code here
 * });
 */

// $events->afterCollections(function ($jigsaw) {
//     // $output = [];
//     // $jigsaw->getSiteData()->posts->first()->collection->each(function($post) use (&$output) {
//     //     $output[] = $post->collection;
//     // });
//     // dd($output);
//     // $jigsaw->getSiteData()->posts->first()->each(function($post){
//     //     print_r($post);
        
//     // });
//     print_r($jigsaw->getSiteData()->posts;
//     dd('x');
// });

$events->afterBuild(App\Listeners\GenerateSitemap::class);
$events->afterBuild(App\Listeners\GenerateIndex::class);
