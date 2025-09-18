import Hero from '../components/Hero';
import FeatureCards from '../components/FeatureCards';
import MarketplacePreview from '../components/MarketplacePreview';
import Footer from '../components/Footer';

export default function Home() {
  return (
    <main>
      <Hero />
      <FeatureCards />
      <MarketplacePreview />
      <Footer />
    </main>
  );
}
