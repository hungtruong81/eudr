"use client";
import { useJsApiLoader, Libraries } from "@react-google-maps/api";
import { ReactNode } from "react";

const libraries: Libraries = ["drawing", "places", "geometry"];

export const GoogleMapsProvider = ({ children }: { children: ReactNode }) => {
  const { isLoaded } = useJsApiLoader({
    id: "google-map-script",
    googleMapsApiKey: process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY!,
    libraries,
  });

  if (!isLoaded) return <div>Đang xử lý...</div>;

  return <>{children}</>;
};
