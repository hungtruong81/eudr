import HarvestDetail from "@/features/farmer/harvest/result/components/harvest-detail";

interface HarvestScheduleDetailPageProps {
  params: Promise<{ harvest_schedule_code: string }>;
}

export default async function HarvestScheduleDetail({
  params,
}: HarvestScheduleDetailPageProps) {
  const { harvest_schedule_code } = await params;

  return (
    <div className="">
      <HarvestDetail harvest_schedule_code={harvest_schedule_code} />
    </div>
  );
}
