import { BaseFilter } from "@/components/base-filter";
import { Form, Select, Input } from "antd";
import React from "react";
import { useTranslations } from "next-intl";

interface PriceFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const PriceFilter = ({ onSearch, onReset, loading }: PriceFilterProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();

  const handleFinish = (values: any) => {
    onSearch(values);
  };

  return (
    <BaseFilter
      form={form}
      onFinish={handleFinish}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="search" label={tc("search")}>
        <Input placeholder={t("search_price_placeholder")} allowClear />
      </Form.Item>
      {/* <Form.Item name="price_type" label={t("price_type")}>
        <Select
          placeholder={tc("select_status")} // Or custom placeholder
          allowClear
          options={[
            { label: t("type_pallet"), value: "pallet" },
            { label: t("type_box"), value: "box" },
            { label: t("type_other"), value: "other" },
          ]}
        />
      </Form.Item> */}
    </BaseFilter>
  );
};

export default PriceFilter;
