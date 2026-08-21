import PlantDetailClient from "@/features/manage-land/plant/components/plant-detail";
import React from "react";

interface PlantDetailPageProps {
  params: Promise<{ plant_code: string }>;
}

export default async function PlantDetailPage({
  params,
}: PlantDetailPageProps) {
  const resolvedParams = await params;
  const { plant_code } = resolvedParams;

  return (
    <main>
      <PlantDetailClient plant_code={plant_code} />
    </main>
  );
}
