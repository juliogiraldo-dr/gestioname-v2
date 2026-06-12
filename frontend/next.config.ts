import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Salida autocontenida para la imagen de producción (docker/node/Dockerfile.prod).
  output: "standalone",
};

export default nextConfig;
