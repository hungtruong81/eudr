import HarvestPlanDetailClient from "@/features/farmer/harvest/plan/components/plan-detail";

interface HarvestPlanDetailPageProps {
  params: Promise<{ harvest_plan_code: string }>;
}

export default async function HarvestPlanDetail({
  params,
}: HarvestPlanDetailPageProps) {
  const { harvest_plan_code } = await params;

  return (
    <div>
      <HarvestPlanDetailClient harvest_plan_code={harvest_plan_code} />
    </div>
  );
}
