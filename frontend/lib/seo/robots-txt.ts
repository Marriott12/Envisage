/**
 * Robots.txt generator for SEO
 * Controls search engine crawler access
 */

interface RobotsRule {
  userAgent: string;
  allow?: string[];
  disallow?: string[];
  crawlDelay?: number;
}

interface RobotsOptions {
  rules: RobotsRule[];
  sitemaps?: string[];
  host?: string;
}

/**
 * Generate robots.txt content
 */
export function generateRobotsTxt(options: RobotsOptions): string {
  const lines: string[] = [];

  // Add rules for each user agent
  options.rules.forEach((rule) => {
    lines.push(`User-agent: ${rule.userAgent}`);

    if (rule.allow) {
      rule.allow.forEach((path) => {
        lines.push(`Allow: ${path}`);
      });
    }

    if (rule.disallow) {
      rule.disallow.forEach((path) => {
        lines.push(`Disallow: ${path}`);
      });
    }

    if (rule.crawlDelay !== undefined) {
      lines.push(`Crawl-delay: ${rule.crawlDelay}`);
    }

    lines.push(''); // Empty line between rules
  });

  // Add sitemaps
  if (options.sitemaps) {
    options.sitemaps.forEach((sitemap) => {
      lines.push(`Sitemap: ${sitemap}`);
    });
    lines.push('');
  }

  // Add host if provided
  if (options.host) {
    lines.push(`Host: ${options.host}`);
  }

  return lines.join('\n');
}

/**
 * Get production robots.txt configuration
 */
export function getProductionRobots(baseUrl: string): string {
  return generateRobotsTxt({
    rules: [
      {
        userAgent: '*',
        allow: ['/'],
        disallow: [
          '/api/',
          '/admin/',
          '/_next/',
          '/checkout/',
          '/account/',
          '/cart/',
          '/*.json$',
          '/*?*sort=',
          '/*?*page=',
        ],
      },
      {
        userAgent: 'Googlebot',
        allow: ['/'],
        disallow: ['/api/', '/admin/', '/checkout/', '/account/'],
      },
      {
        userAgent: 'Bingbot',
        allow: ['/'],
        disallow: ['/api/', '/admin/', '/checkout/', '/account/'],
        crawlDelay: 1,
      },
    ],
    sitemaps: [
      `${baseUrl}/sitemap.xml`,
      `${baseUrl}/sitemap-products.xml`,
      `${baseUrl}/sitemap-blog.xml`,
    ],
    host: baseUrl,
  });
}

/**
 * Get development robots.txt configuration (block all)
 */
export function getDevelopmentRobots(): string {
  return generateRobotsTxt({
    rules: [
      {
        userAgent: '*',
        disallow: ['/'],
      },
    ],
  });
}

/**
 * Get staging robots.txt configuration (block all except specific bots)
 */
export function getStagingRobots(): string {
  return generateRobotsTxt({
    rules: [
      {
        userAgent: '*',
        disallow: ['/'],
      },
      {
        userAgent: 'Googlebot',
        disallow: ['/'],
      },
    ],
  });
}

export default {
  generateRobotsTxt,
  getProductionRobots,
  getDevelopmentRobots,
  getStagingRobots,
};
