import { BaseFilter } from "@/components/base-filter";
import { Form, Input } from "antd";
import React from "react";
import { IGetPlantParams } from "../actions";

interface IPlantFilterProps {
  onSearch: (params: Partial<IGetPlantParams>) => void;
}
import { useTranslations } from "next-intl";

const PlantFilter = ({ onSearch }: IPlantFilterProps) => {
  const tCommon = useTranslations("Common");
  const handleFinish = (values: Partial<IGetPlantParams>) => {
    onSearch({
      search: values.search,
    });
  };

  const handleReset = () => {
    onSearch({ search: "" });
  };
  return (
    <BaseFilter onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tCommon("search")}>
        <Input placeholder={tCommon("search_placeholder")} allowClear />
      </Form.Item>
    </BaseFilter>
  );
};

export default PlantFilter;
