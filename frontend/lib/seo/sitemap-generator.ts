/**
 * Sitemap generator for SEO
 * Generates XML sitemaps for search engines
 */

interface SitemapUrl {
  loc: string;
  lastmod?: string;
  changefreq?: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never';
  priority?: number;
}

interface SitemapOptions {
  baseUrl: string;
  pretty?: boolean;
}

/**
 * Generate XML sitemap from URLs
 */
export function generateSitemap(urls: SitemapUrl[], options: SitemapOptions): string {
  const { baseUrl, pretty = true } = options;
  const indent = pretty ? '  ' : '';
  const newline = pretty ? '\n' : '';

  const urlEntries = urls.map((url) => {
    const loc = url.loc.startsWith('http') ? url.loc : `${baseUrl}${url.loc}`;
    
    let entry = `${indent}<url>${newline}`;
    entry += `${indent}${indent}<loc>${escapeXml(loc)}</loc>${newline}`;
    
    if (url.lastmod) {
      entry += `${indent}${indent}<lastmod>${url.lastmod}</lastmod>${newline}`;
    }
    
    if (url.changefreq) {
      entry += `${indent}${indent}<changefreq>${url.changefreq}</changefreq>${newline}`;
    }
    
    if (url.priority !== undefined) {
      entry += `${indent}${indent}<priority>${url.priority.toFixed(1)}</priority>${newline}`;
    }
    
    entry += `${indent}</url>`;
    return entry;
  }).join(newline);

  return `<?xml version="1.0" encoding="UTF-8"?>${newline}` +
    `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${newline}` +
    urlEntries +
    `${newline}</urlset>`;
}

/**
 * Generate sitemap index for multiple sitemaps
 */
export function generateSitemapIndex(sitemaps: Array<{ loc: string; lastmod?: string }>, baseUrl: string): string {
  const entries = sitemaps.map((sitemap) => {
    const loc = sitemap.loc.startsWith('http') ? sitemap.loc : `${baseUrl}${sitemap.loc}`;
    
    return `  <sitemap>
    <loc>${escapeXml(loc)}</loc>${sitemap.lastmod ? `
    <lastmod>${sitemap.lastmod}</lastmod>` : ''}
  </sitemap>`;
  }).join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries}
</sitemapindex>`;
}

/**
 * Get static pages for sitemap
 */
export function getStaticPages(baseUrl: string): SitemapUrl[] {
  return [
    {
      loc: '/',
      changefreq: 'daily',
      priority: 1.0,
    },
    {
      loc: '/products',
      changefreq: 'daily',
      priority: 0.9,
    },
    {
      loc: '/categories',
      changefreq: 'weekly',
      priority: 0.8,
    },
    {
      loc: '/about',
      changefreq: 'monthly',
      priority: 0.6,
    },
    {
      loc: '/contact',
      changefreq: 'monthly',
      priority: 0.6,
    },
    {
      loc: '/blog',
      changefreq: 'daily',
      priority: 0.7,
    },
    {
      loc: '/faq',
      changefreq: 'monthly',
      priority: 0.5,
    },
    {
      loc: '/terms',
      changefreq: 'yearly',
      priority: 0.3,
    },
    {
      loc: '/privacy',
      changefreq: 'yearly',
      priority: 0.3,
    },
  ];
}

/**
 * Fetch dynamic product pages
 */
export async function getProductPages(): Promise<SitemapUrl[]> {
  try {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/products?fields=id,updatedAt`);
    const products = await response.json();

    return products.map((product: any) => ({
      loc: `/products/${product.id}`,
      lastmod: product.updatedAt,
      changefreq: 'weekly' as const,
      priority: 0.8,
    }));
  } catch (error) {
    console.error('Failed to fetch products for sitemap:', error);
    return [];
  }
}

/**
 * Fetch dynamic category pages
 */
export async function getCategoryPages(): Promise<SitemapUrl[]> {
  try {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/categories?fields=slug,updatedAt`);
    const categories = await response.json();

    return categories.map((category: any) => ({
      loc: `/categories/${category.slug}`,
      lastmod: category.updatedAt,
      changefreq: 'weekly' as const,
      priority: 0.7,
    }));
  } catch (error) {
    console.error('Failed to fetch categories for sitemap:', error);
    return [];
  }
}

/**
 * Fetch dynamic blog pages
 */
export async function getBlogPages(): Promise<SitemapUrl[]> {
  try {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/blog?fields=slug,updatedAt`);
    const posts = await response.json();

    return posts.map((post: any) => ({
      loc: `/blog/${post.slug}`,
      lastmod: post.updatedAt,
      changefreq: 'monthly' as const,
      priority: 0.6,
    }));
  } catch (error) {
    console.error('Failed to fetch blog posts for sitemap:', error);
    return [];
  }
}

/**
 * Generate complete sitemap with all pages
 */
export async function generateCompleteSitemap(baseUrl: string): Promise<string> {
  const [staticPages, productPages, categoryPages, blogPages] = await Promise.all([
    Promise.resolve(getStaticPages(baseUrl)),
    getProductPages(),
    getCategoryPages(),
    getBlogPages(),
  ]);

  const allPages = [
    ...staticPages,
    ...productPages,
    ...categoryPages,
    ...blogPages,
  ];

  return generateSitemap(allPages, { baseUrl });
}

/**
 * Escape special XML characters
 */
function escapeXml(unsafe: string): string {
  return unsafe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

export default {
  generateSitemap,
  generateSitemapIndex,
  generateCompleteSitemap,
  getStaticPages,
  getProductPages,
  getCategoryPages,
  getBlogPages,
};
