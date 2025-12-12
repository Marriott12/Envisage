/**
 * Structured data (JSON-LD) generators for SEO
 * Schema.org markup for rich search results
 */

interface StructuredDataProps {
  '@context': string;
  '@type': string;
  [key: string]: any;
}

/**
 * Generate Organization structured data
 */
export function generateOrganizationSchema(data: {
  name: string;
  url: string;
  logo: string;
  description?: string;
  contactPoint?: {
    telephone: string;
    contactType: string;
    email?: string;
  };
  sameAs?: string[];
}): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: data.name,
    url: data.url,
    logo: data.logo,
    description: data.description,
    contactPoint: data.contactPoint ? {
      '@type': 'ContactPoint',
      telephone: data.contactPoint.telephone,
      contactType: data.contactPoint.contactType,
      email: data.contactPoint.email,
    } : undefined,
    sameAs: data.sameAs,
  };
}

/**
 * Generate Product structured data
 */
export function generateProductSchema(product: {
  name: string;
  description: string;
  image: string | string[];
  sku: string;
  brand?: string;
  offers: {
    price: number;
    priceCurrency: string;
    availability: string;
    url?: string;
    priceValidUntil?: string;
    seller?: string;
  };
  aggregateRating?: {
    ratingValue: number;
    reviewCount: number;
    bestRating?: number;
    worstRating?: number;
  };
  review?: Array<{
    author: string;
    datePublished: string;
    reviewBody: string;
    reviewRating: number;
  }>;
}): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description: product.description,
    image: product.image,
    sku: product.sku,
    brand: product.brand ? {
      '@type': 'Brand',
      name: product.brand,
    } : undefined,
    offers: {
      '@type': 'Offer',
      price: product.offers.price,
      priceCurrency: product.offers.priceCurrency,
      availability: `https://schema.org/${product.offers.availability}`,
      url: product.offers.url,
      priceValidUntil: product.offers.priceValidUntil,
      seller: product.offers.seller ? {
        '@type': 'Organization',
        name: product.offers.seller,
      } : undefined,
    },
    aggregateRating: product.aggregateRating ? {
      '@type': 'AggregateRating',
      ratingValue: product.aggregateRating.ratingValue,
      reviewCount: product.aggregateRating.reviewCount,
      bestRating: product.aggregateRating.bestRating || 5,
      worstRating: product.aggregateRating.worstRating || 1,
    } : undefined,
    review: product.review?.map((review) => ({
      '@type': 'Review',
      author: {
        '@type': 'Person',
        name: review.author,
      },
      datePublished: review.datePublished,
      reviewBody: review.reviewBody,
      reviewRating: {
        '@type': 'Rating',
        ratingValue: review.reviewRating,
        bestRating: 5,
      },
    })),
  };
}

/**
 * Generate Breadcrumb structured data
 */
export function generateBreadcrumbSchema(breadcrumbs: Array<{
  name: string;
  url: string;
}>): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: breadcrumbs.map((crumb, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: crumb.name,
      item: crumb.url,
    })),
  };
}

/**
 * Generate Article structured data
 */
export function generateArticleSchema(article: {
  headline: string;
  description: string;
  image: string | string[];
  author: string;
  datePublished: string;
  dateModified?: string;
  publisher: {
    name: string;
    logo: string;
  };
}): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: article.headline,
    description: article.description,
    image: article.image,
    author: {
      '@type': 'Person',
      name: article.author,
    },
    datePublished: article.datePublished,
    dateModified: article.dateModified || article.datePublished,
    publisher: {
      '@type': 'Organization',
      name: article.publisher.name,
      logo: {
        '@type': 'ImageObject',
        url: article.publisher.logo,
      },
    },
  };
}

/**
 * Generate WebSite structured data with search action
 */
export function generateWebSiteSchema(data: {
  name: string;
  url: string;
  searchUrl: string;
}): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: data.name,
    url: data.url,
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${data.searchUrl}?q={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };
}

/**
 * Generate FAQ structured data
 */
export function generateFAQSchema(faqs: Array<{
  question: string;
  answer: string;
}>): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map((faq) => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: faq.answer,
      },
    })),
  };
}

/**
 * Generate LocalBusiness structured data
 */
export function generateLocalBusinessSchema(business: {
  name: string;
  description: string;
  image: string;
  telephone: string;
  email?: string;
  address: {
    streetAddress: string;
    addressLocality: string;
    addressRegion: string;
    postalCode: string;
    addressCountry: string;
  };
  geo?: {
    latitude: number;
    longitude: number;
  };
  openingHours?: string[];
  priceRange?: string;
}): StructuredDataProps {
  return {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    name: business.name,
    description: business.description,
    image: business.image,
    telephone: business.telephone,
    email: business.email,
    address: {
      '@type': 'PostalAddress',
      streetAddress: business.address.streetAddress,
      addressLocality: business.address.addressLocality,
      addressRegion: business.address.addressRegion,
      postalCode: business.address.postalCode,
      addressCountry: business.address.addressCountry,
    },
    geo: business.geo ? {
      '@type': 'GeoCoordinates',
      latitude: business.geo.latitude,
      longitude: business.geo.longitude,
    } : undefined,
    openingHoursSpecification: business.openingHours?.map((hours) => ({
      '@type': 'OpeningHoursSpecification',
      dayOfWeek: hours,
    })),
    priceRange: business.priceRange,
  };
}

/**
 * Component for rendering structured data
 */
interface StructuredDataComponentProps {
  data: StructuredDataProps | StructuredDataProps[];
}

export function StructuredData({ data }: StructuredDataComponentProps) {
  const jsonLd = Array.isArray(data)
    ? { '@graph': data }
    : data;

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
    />
  );
}

export default {
  generateOrganizationSchema,
  generateProductSchema,
  generateBreadcrumbSchema,
  generateArticleSchema,
  generateWebSiteSchema,
  generateFAQSchema,
  generateLocalBusinessSchema,
  StructuredData,
};
