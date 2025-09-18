export default function FeatureCards() {
  const features = [
    {
      title: 'Marketplace',
      description: 'Buy, sell, and discover tech products and services tailored for Africa.',
      icon: '🛒',
    },
    {
      title: 'Training & Events',
      description: 'Upskill with expert-led courses, workshops, and community events.',
      icon: '🎓',
    },
    {
      title: 'Client Portal',
      description: 'Manage projects, support, and collaboration in one secure dashboard.',
      icon: '🔒',
    },
    {
      title: 'Affiliate Dashboard',
      description: 'Earn by referring clients and partners to Envisage solutions.',
      icon: '🤝',
    },
  ];

  return (
    <section className="py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
      {features.map((feature) => (
        <div key={feature.title} className="bg-white rounded-lg shadow p-6 flex flex-col items-center text-center">
          <span className="text-4xl mb-4">{feature.icon}</span>
          <h3 className="font-bold text-xl mb-2 text-blue-700">{feature.title}</h3>
          <p className="text-gray-600">{feature.description}</p>
        </div>
      ))}
    </section>
  );
}
