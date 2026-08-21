import LandDetail from "@/features/manage-land/land/components/land-detail";

interface LandDetailPageProps {
  params: Promise<{ plot_code: string }>;
}

export default async function LandDetailPage({ params }: LandDetailPageProps) {
  const { plot_code } = await params;

  return <LandDetail plot_code={plot_code} />;
}
