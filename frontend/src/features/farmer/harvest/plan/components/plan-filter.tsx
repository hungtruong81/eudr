import { BaseFilter } from "@/components/base-filter";
import { Form, Select } from "antd";
import { IGetPlanParams } from "../actions";

import { DatePicker } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IContract } from "@/lib/types";
import { getContracts } from "@/lib/api";
import { rangePresets } from "@/constants/range-date";
import { useTranslations } from "next-intl";
const { RangePicker } = DatePicker;

interface IPlanFilterProps {
  onSearch: (params: Partial<IGetPlanParams>) => void;
}
const PlanFilter = ({ onSearch }: IPlanFilterProps) => {
  const t = useTranslations("Harvest.Plan");

  const handleFinish = (values: Partial<IGetPlanParams>) => {
    onSearch({
      search: values.search,
      harvest_start_date: values.harvest_start_date?.[0],
      harvest_end_date: values.harvest_start_date?.[1],
      tapping_regime: values.tapping_regime,
      contract_code: values.contract_code,
    });
  };

  const handleReset = () => {
    onSearch({
      search: "",
      contract_code: "",
      tapping_regime: undefined,
      harvest_start_date: undefined,
      harvest_end_date: undefined,
    });
  };

  const mapContractOptions = (item: IContract) => ({
    label: item.contract_code,
    value: item.contract_code,
  });

  return (
    <BaseFilter onFinish={handleFinish} onReset={handleReset}>
      <Form.Item
        name="contract_code"
        className="mb-0"
        label={t("contract_code")}>
        <InfiniteScrollSelect<IContract>
          queryKey={["contracts-select"]}
          fetchFn={getContracts}
          mapOption={mapContractOptions}
          allowClear
          placeholder={t("select_contract")}
        />
      </Form.Item>

      <Form.Item
        name="harvest_start_date"
        className="mb-0"
        label={t("start_date")}>
        <RangePicker
          placeholder={[t("start_date"), t("end_date")]}
          presets={rangePresets}
          format="DD/MM/YYYY"
        />
      </Form.Item>

      <Form.Item
        name="tapping_regime"
        className="mb-0"
        label={t("tapping_regime")}>
        <Select
          options={[
            {
              label: t("all"),
              value: "",
            },
            {
              label: "D1",
              value: "D1",
            },
            {
              label: "D2",
              value: "D2",
            },
            {
              label: "D3",
              value: "D3",
            },
            {
              label: "D4",
              value: "D4",
            },
            {
              label: "D5",
              value: "D5",
            },
            {
              label: "D6",
              value: "D6",
            },
          ]}
          placeholder={t("select_tapping_regime")}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default PlanFilter;
