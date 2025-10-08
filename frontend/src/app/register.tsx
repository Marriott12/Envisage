export default function Register() {
  return (
    <section className="py-16 px-4 max-w-md mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Register</h2>
      <form className="bg-white rounded-lg shadow p-8 flex flex-col gap-4">
        <input type="text" placeholder="Full Name" className="border rounded px-4 py-2" />
        <input type="email" placeholder="Email" className="border rounded px-4 py-2" />
        <input type="password" placeholder="Password" className="border rounded px-4 py-2" />
        <button type="submit" className="px-8 py-3 bg-blue-700 text-white rounded hover:bg-blue-800 transition">Create Account</button>
      </form>
      <p className="mt-4 text-center text-gray-600">Already have an account? <a href="/login" className="text-blue-700 hover:underline">Login</a></p>
    </section>
  );
}
