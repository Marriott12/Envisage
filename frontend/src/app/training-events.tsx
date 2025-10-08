export default function TrainingEvents() {
  return (
    <section className="py-16 px-4 max-w-5xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Training & Events</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Full Stack Bootcamp</h3>
          <p className="text-gray-600">Intensive coding bootcamp for aspiring developers. Next cohort: October 2025.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Digital Marketing Workshop</h3>
          <p className="text-gray-600">Learn the latest in SEO, social media, and online advertising. Register now!</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Cloud Fundamentals</h3>
          <p className="text-gray-600">Get hands-on with AWS, Azure, and Google Cloud. For all skill levels.</p>
        </div>
      </div>
    </section>
  );
}
