import { BaseFilter } from "@/components/base-filter";
import { Form, Select, Input } from "antd";
import React from "react";
import { useTranslations } from "next-intl";

interface CompanyFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const CompanyFilter = ({ onSearch, onReset, loading }: CompanyFilterProps) => {
  const [form] = Form.useForm();
  const tc = useTranslations("Common");

  return (
    <BaseFilter
      form={form}
      onFinish={onSearch}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="status" label={tc("status")}>
        <Select
          placeholder={tc("select_status")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: tc("status_all"), value: "all" },
            { label: tc("status_active"), value: "active" },
            { label: tc("status_inactive"), value: "inactive" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default CompanyFilter;
