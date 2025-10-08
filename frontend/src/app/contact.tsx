export default function Contact() {
  return (
    <section className="py-16 px-4 max-w-3xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Contact & Booking</h2>
      <form className="bg-white rounded-lg shadow p-8 flex flex-col gap-4">
        <input type="text" placeholder="Your Name" className="border rounded px-4 py-2" />
        <input type="email" placeholder="Your Email" className="border rounded px-4 py-2" />
        <textarea placeholder="Your Message" className="border rounded px-4 py-2" rows={5} />
        <button type="submit" className="px-8 py-3 bg-blue-700 text-white rounded hover:bg-blue-800 transition">Send Message</button>
      </form>
    </section>
  );
}
