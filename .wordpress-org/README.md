# WordPress.org plugin assets

Directory assets for https://wordpress.org/plugins/broadside-blocks/ — **not**
part of the installable plugin. Deployed to SVN `assets/` by
`.github/workflows/deploy.yml` / `assets.yml`.

| File | Size |
| ---- | ---- |
| `icon-256x256.png` | 256×256 |
| `icon-128x128.png` | 128×128 |
| `banner-1544x500.png` | 1544×500 (retina banner) |
| `banner-772x250.png` | 772×250 (standard banner) |

Sources: `.github/assets/banner.png` / `banner.svg`. Regenerate:

```bash
cp .github/assets/banner.png .wordpress-org/banner-1544x500.png
magick .github/assets/banner.png -resize 772x250! .wordpress-org/banner-772x250.png
# icons: rsvg-convert the hexmark SVG (see package history)
```
