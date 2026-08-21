import { Button, Form, Input, Space } from "antd";
import React from "react";
import { SearchOutlined, ClearOutlined } from "@ant-design/icons";
import { useTranslations } from "next-intl";
import { BaseFilter } from "@/components/base-filter";

interface GradeFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const GradeFilter = ({ onSearch, onReset, loading }: GradeFilterProps) => {
  const [form] = Form.useForm();
  const tc = useTranslations("Common");

  const handleReset = () => {
    form.resetFields();
    onReset();
  };

  return (
    <BaseFilter
      form={form}
      onFinish={onSearch}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="search">
        <Input
          placeholder={tc("search_placeholder")}
          allowClear
          prefix={<SearchOutlined />}
          style={{ width: 250 }}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default GradeFilter;
