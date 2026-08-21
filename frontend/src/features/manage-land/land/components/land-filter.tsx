import { BaseFilter } from "@/components/base-filter";
import { Form, Input } from "antd";
import React from "react";
import { IGetLandParams } from "../actions";
import { useTranslations } from "next-intl";

interface LandFilterProps {
  onSearch: (value: Partial<IGetLandParams>) => void;
  loading?: boolean;
}

const LandFilter: React.FC<LandFilterProps> = ({ onSearch, loading }) => {
  const t = useTranslations("ManageLand.Land");
  const tCommon = useTranslations("Common");
  const handleFinish = (values: any) => {
    onSearch({
      search: values.search,
    });
  };

  const handleReset = () => {
    onSearch({});
  };

  return (
    <BaseFilter onFinish={handleFinish} onReset={handleReset} loading={loading}>
      <Form.Item name="search" className="mb-0" label={tCommon("search")}>
        <Input placeholder={tCommon("search_placeholder")} allowClear />
      </Form.Item>
    </BaseFilter>
  );
};

export default LandFilter;
