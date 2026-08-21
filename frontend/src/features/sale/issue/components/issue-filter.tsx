import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getOrders } from "@/features/sale/order/actions";
import { Form, DatePicker, Select, Input } from "antd";
import React from "react";
import { useTranslations } from "next-intl";
import { IOrder } from "../../order/types";
import { rangePresets } from "@/constants/range-date";

const { RangePicker } = DatePicker;

interface IssueFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const IssueFilter = ({ onSearch, onReset, loading }: IssueFilterProps) => {
  const t = useTranslations("Issue");
  const tc = useTranslations("Common");
  const ts = useTranslations("Status");
  const [form] = Form.useForm();

  const handleFinish = (values: any) => {
    const { date_range, ...rest } = values;
    onSearch({
      ...rest,
      issue_date_from: date_range?.[0]?.format("YYYY-MM-DD"),
      issue_date_to: date_range?.[1]?.format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter
      form={form}
      onFinish={handleFinish}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="search" label={tc("search")}>
        <Input placeholder={t("issue_code")} allowClear />
      </Form.Item>
      <Form.Item name="date_range" label={tc("created_at")}>
        <RangePicker
          presets={rangePresets}
          format="DD/MM/YYYY"
          placeholder={[tc("from_date"), tc("to_date")]}
        />
      </Form.Item>
      <Form.Item name="sale_order_id" label={t("sale_order")}>
        <InfiniteScrollSelect<IOrder>
          queryKey={["orders-select"]}
          fetchFn={getOrders}
          mapOption={(item) => ({
            label: `${item.sale_order_code} (${item.customer_name})`,
            value: String(item.sale_order_id),
          })}
          placeholder={t("select_order")}
        />
      </Form.Item>
      <Form.Item name="status" label={tc("status")}>
        <Select
          placeholder={tc("select_status")}
          allowClear
          options={[
            { label: ts("all"), value: "all" },
            { label: ts("draft"), value: "draft" },
            { label: ts("issued"), value: "issued" },
            { label: ts("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default IssueFilter;
