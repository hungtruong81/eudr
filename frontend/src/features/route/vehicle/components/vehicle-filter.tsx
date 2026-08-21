"use client";
import { BaseFilter } from "@/components/base-filter";
import { Form, Input } from "antd";

import { useTranslations } from "next-intl";

interface VehicleFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const VehicleFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: VehicleFilterProps) => {
  const tCommon = useTranslations("Common");
  const tVehicle = useTranslations("Route.vehicle");

  return (
    <BaseFilter
      form={filterForm}
      onFinish={handleFilter}
      onReset={handleResetFilter}>
      <Form.Item name="search" label={tCommon("search")}>
        <Input
          placeholder={tVehicle("search_placeholder")}
          style={{ width: 250 }}
          allowClear
        />
      </Form.Item>
    </BaseFilter>
  );
};
