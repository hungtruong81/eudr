"use client";
import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { DatePicker, Form } from "antd";
import { getPlan, IGetSchedulesParams } from "../../plan/actions";
import { IHarvestPlan } from "../../plan/types";
import { useTranslations } from "next-intl";

interface IHarvestFilterProps {
  onSearch: (params: Partial<IGetSchedulesParams>) => void;
}

const HarvestFilter = ({ onSearch }: IHarvestFilterProps) => {
  const t = useTranslations("Harvest.Result");

  const handleFinish = (values: any) => {
    onSearch({
      harvest_plan_code: values.harvest_plan_code,
      pickup_date: values.pickup_date?.format("YYYY-MM-DD"),
    });
  };

  const handleReset = () => {
    onSearch({
      harvest_plan_code: undefined,
      pickup_date: "",
    });
  };

  const mapPlanOptions = (item: IHarvestPlan) => ({
    label: item.harvest_plan_code?.toUpperCase(),
    value: item.harvest_plan_code,
  });

  return (
    <BaseFilter onFinish={handleFinish} onReset={handleReset}>
      <Form.Item
        name="harvest_plan_code"
        className="mb-0"
        label={t("plan_code")}>
        <InfiniteScrollSelect<IHarvestPlan>
          queryKey={["harvest-plan-select"]}
          fetchFn={getPlan}
          mapOption={mapPlanOptions}
          allowClear
          placeholder={t("select_plan")}
        />
      </Form.Item>

      <Form.Item name="pickup_date" className="mb-0" label={t("harvest_date")}>
        <DatePicker placeholder={t("harvest_date")} format="DD/MM/YYYY" />
      </Form.Item>
    </BaseFilter>
  );
};

export default HarvestFilter;
