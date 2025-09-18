import Image from 'next/image';

export default function Hero() {
  return (
    <section className="flex flex-col items-center justify-center py-16 bg-gradient-to-br from-blue-900 via-blue-700 to-blue-500 text-white">
      <Image src="/assets/logo.png" alt="Envisage Logo" width={120} height={120} className="mb-6" />
      <h1 className="text-4xl md:text-6xl font-bold mb-4 text-center">Envisage Technology Zambia</h1>
      <p className="text-lg md:text-2xl mb-8 text-center max-w-2xl">Empowering Africa with innovative digital solutions, marketplace, training, and client services.</p>
      <a href="#marketplace" className="px-8 py-3 bg-white text-blue-700 font-semibold rounded shadow hover:bg-blue-100 transition">Explore Marketplace</a>
    </section>
  );
}
