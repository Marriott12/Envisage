export default function Services() {
  return (
    <section className="py-16 px-4 max-w-5xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Our Services</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Web & App Development</h3>
          <p className="text-gray-600">Custom websites, mobile apps, and digital platforms for businesses and organizations.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Cloud & DevOps</h3>
          <p className="text-gray-600">Cloud migration, automation, and scalable infrastructure for modern enterprises.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Digital Marketing</h3>
          <p className="text-gray-600">SEO, social media, and online campaigns to grow your brand and reach.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">IT Consulting</h3>
          <p className="text-gray-600">Expert advice, audits, and digital transformation strategies for your business.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Training & Workshops</h3>
          <p className="text-gray-600">Upskill your team with hands-on training in tech, business, and innovation.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Support & Maintenance</h3>
          <p className="text-gray-600">Ongoing support, updates, and troubleshooting for your digital assets.</p>
        </div>
      </div>
    </section>
  );
}
