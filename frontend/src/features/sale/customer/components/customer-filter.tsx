import { BaseFilter } from "@/components/base-filter";
import { Form, Input, Select } from "antd";
import React from "react";
import { useTranslations } from "next-intl";

interface CustomerFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const CustomerFilter = ({
  onSearch,
  onReset,
  loading,
}: CustomerFilterProps) => {
  const t = useTranslations("Customer");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();

  return (
    <BaseFilter
      form={form}
      onFinish={onSearch}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="search" label={tc("search")}>
        <Input placeholder={t("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="status" label={tc("status")}>
        <Select
          placeholder={tc("select_status")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: ts("active"), value: "active" },
            { label: ts("inactive"), value: "inactive" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default CustomerFilter;
