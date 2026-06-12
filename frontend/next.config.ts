import type { NextConfig } from "next";

// Origen interno del backend (Laravel) dentro de la red del despliegue.
// En producción de un solo host, el frontend Next sirve la web y reescribe
// /api/* y /health hacia el backend, de modo que todo comparte un único dominio.
const BACKEND_ORIGIN = process.env.BACKEND_ORIGIN ?? "http://backend:8000";

const nextConfig: NextConfig = {
  // Salida autocontenida para la imagen de producción (docker/node/Dockerfile.prod).
  output: "standalone",
  async rewrites() {
    return [
      { source: "/api/:path*", destination: `${BACKEND_ORIGIN}/api/:path*` },
      { source: "/health", destination: `${BACKEND_ORIGIN}/health` },
    ];
  },
};

export default nextConfig;
