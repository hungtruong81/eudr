import { BaseFilter } from "@/components/base-filter";
import { Form, Input, Select } from "antd";
import { useTranslations } from "next-intl";
import React from "react";
import { IGetConnectionParams } from "../actions";

interface IConnectionFilterProps {
  onSearch: (params: Partial<IGetConnectionParams>) => void;
}

const ConnectionFilter = ({ onSearch }: IConnectionFilterProps) => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");
  const tRegister = useTranslations("Register");

  const handleFinish = (values: Partial<IGetConnectionParams>) => {
    onSearch({
      ...values,
    });
  };

  const handleReset = () => {
    onSearch({});
  };

  return (
    <BaseFilter onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tCommon("search")}>
        <Input placeholder={tCommon("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item
        name="type"
        className="mb-0"
        initialValue="all"
        label={tConnection("filter_type")}>
        <Select
          placeholder={tConnection("filter_type")}
          style={{ width: 150 }}
          options={[
            { label: tCommon("all"), value: "all" },
            { label: tConnection("received"), value: "received" },
            { label: tConnection("sent"), value: "sent" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="status"
        className="mb-0"
        initialValue="all"
        label={tCommon("status")}>
        <Select
          placeholder={tCommon("status")}
          style={{ width: 150 }}
          options={[
            { label: tConnection("all_status"), value: "all" },
            { label: tConnection("pending"), value: "pending" },
            { label: tConnection("accepted"), value: "accepted" },
            { label: tConnection("rejected"), value: "rejected" },
            { label: tConnection("cancelled"), value: "cancelled" },
            { label: tConnection("blocked"), value: "blocked" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="account_type"
        className="mb-0"
        label={tConnection("filter_account_type")}>
        <Select
          placeholder={tConnection("filter_account_type")}
          style={{ width: 150 }}
          allowClear
          options={[
            { label: tCommon("all"), value: "all" },
            { label: tRegister("farmer"), value: "farmer" },
            { label: tRegister("purchaser"), value: "purchaser" },
            { label: tRegister("transport"), value: "transport" },
            { label: tRegister("factory"), value: "factory" },
            { label: tRegister("sales"), value: "sales" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default ConnectionFilter;
