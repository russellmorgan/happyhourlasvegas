# Foodie Friday video (removed from the homepage, 2026-11-09)

The homepage "video + newsletter" section was replaced by the blog cards section (`[hhv_blog_posts]`, see `inc/blog.php`).
Everything needed to bring the video back is kept here and in the theme.

## The video

- YouTube link: https://www.youtube.com/watch?v=eBJz2wLU-qE
- It is still saved in the homepage's "Featured YouTube video" field (Pages > Home > Homepage fields).
- The player code is still in the theme: block `hhv/home-video` (`blocks/home-video/`), styles `.hhv-yt` in `assets/css/home.css`.
  In any page or template, the block `<!-- wp:hhv/home-video /-->` shows the homepage's video.

Plain YouTube embed code (works anywhere, e.g. a Custom HTML block):

```html
<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/eBJz2wLU-qE" title="Foodie Friday on NBC Las Vegas" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
```

## The removed homepage section (templates/front-page.html)

```html
<!-- wp:group {"className":"hhv-band hhv-band--cream hhv-home-video","layout":{"type":"default"}} -->
<div class="wp-block-group hhv-band hhv-band--cream hhv-home-video">
<!-- wp:group {"className":"hhv-wrap hhv-home-video__grid","layout":{"type":"default"}} -->
<div class="wp-block-group hhv-wrap hhv-home-video__grid">
<!-- wp:hhv/home-video /-->
<!-- wp:group {"className":"hhv-home-video__news","layout":{"type":"default"}} -->
<div class="wp-block-group hhv-home-video__news">
<!-- wp:hhv/newsletter {"button":"Join","note":""} /-->
<!-- wp:paragraph -->
<p>Made with <span class="hhv-heart">♥</span> in Las Vegas.</p>
<!-- /wp:paragraph -->
<!-- wp:hhv/home-newsletter-link /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

## Video schema (VideoObject)

This was in the Custom HTML block at the bottom of the homepage content. Google expects the video to be on the page
it describes, so it was removed together with the video. Paste it back (inside `<script type="application/ld+json">`)
on whichever page shows the video.

```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Las Vegas Happy Hours — Lexie's Bistro Henderson | Pasta Cucina Sunset Station | Foodie Friday NBC Oct 17",
  "thumbnailUrl": "https://img.youtube.com/vi/eBJz2wLU-qE/maxresdefault.jpg",
  "description": "Two verified Las Vegas happy hours this week — Lexie's Bistro in Henderson where Raiders players hang out on their days off, and Pasta Cucina at Sunset Station, the only happy hour in Las Vegas with Cacio e Pepe on the menu for $8. Andrew 'The Happy Hour Guy' Morgan and Al Mancini on NBC News 3 Foodie Friday.",
  "uploadDate": "2026-10-17",
  "embedUrl": "https://www.youtube.com/embed/eBJz2wLU-qE",
  "contentUrl": "https://www.youtube.com/watch?v=eBJz2wLU-qE",
  "duration": "PT2M30S",
  "width": 1280,
  "height": 720,
  "isFamilyFriendly": true,
  "publisher": {
    "@type": "Organization",
    "name": "Happy Hour Vegas",
    "url": "https://happyhourvegas.com",
    "logo": {
      "@type": "ImageObject",
      "url": "https://happyhourvegas.com/wp-content/uploads/2024/06/Happy-Hour-Vegas-Logo-415-x-75.svg"
    }
  },
  "author": {
    "@type": "Person",
    "name": "Andrew Morgan",
    "url": "https://happyhourvegas.com"
  },
  "inLanguage": "en-US",
  "keywords": "Las Vegas happy hour, Lexie's Bistro Henderson happy hour, Pasta Cucina Sunset Station happy hour, Henderson happy hour, Cacio e Pepe happy hour, National Happy Hour Day, Foodie Friday NBC Las Vegas, Happy Hour Vegas"
}

```
