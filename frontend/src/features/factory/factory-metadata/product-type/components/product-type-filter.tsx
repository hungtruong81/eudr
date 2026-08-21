"use client";
import { BaseFilter } from "@/components/base-filter";
import { Form, Select } from "antd";
import { useTranslations } from "next-intl";

interface ProductTypeFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const ProductTypeFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: ProductTypeFilterProps) => {
  const t = useTranslations("Factory.metadata.product_type");

  return (
    <BaseFilter
      form={filterForm}
      onFinish={handleFilter}
      onReset={handleResetFilter}>
      <Form.Item name="product_type_category" label={t("category")}>
        <Select
          options={[
            { value: "scrap_rubber", label: t("scrap_rubber") },
            { value: "concentrated_latex", label: t("concentrated_latex") },
          ]}
          placeholder={t("select_category")}
          style={{ width: 200 }}
          allowClear
        />
      </Form.Item>
    </BaseFilter>
  );
};
