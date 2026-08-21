import { BaseFilter } from "@/components/base-filter";
import { Form, Select, Input } from "antd";
import React from "react";
import { useTranslations } from "next-intl";

interface PalletFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const PalletFilter = ({ onSearch, onReset, loading }: PalletFilterProps) => {
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
        <Input placeholder={t("pallet_code")} allowClear />
      </Form.Item>
      <Form.Item name="status" label={t("status")}>
        <Select
          placeholder={tc("select_status")}
          allowClear
          options={[
            { label: t("status_draft"), value: "draft" },
            { label: t("status_packed"), value: "packed" },
            { label: t("status_shipped"), value: "shipped" },
            { label: t("status_cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default PalletFilter;
