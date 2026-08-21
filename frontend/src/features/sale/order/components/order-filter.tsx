import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { rangePresets } from "@/constants/range-date";
import { getCustomers } from "@/features/sale/customer/actions";
import { Form, DatePicker, Select } from "antd";
import React from "react";
import { IGetOrderParams } from "../actions";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface OrderFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const OrderFilter = ({ onSearch, onReset, loading }: OrderFilterProps) => {
  const ts = useTranslations("Sales");
  const tst = useTranslations("Status");
  const tc = useTranslations("Common");
  const tr = useTranslations("Register");
  const [form] = Form.useForm();

  const handleFinish = (values: any) => {
    const { date_range, ...rest } = values;
    onSearch({
      ...rest,
      order_date_from: date_range?.[0]?.format("YYYY-MM-DD"),
      order_date_to: date_range?.[1]?.format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter
      form={form}
      onFinish={handleFinish}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="date_range" label={tc("created_at")}>
        <RangePicker
          presets={rangePresets}
          format="DD/MM/YYYY"
          placeholder={[tc("from_date"), tc("to_date")]}
        />
      </Form.Item>
      <Form.Item name="buyer_type" label={ts("buyer_type")}>
        <Select
          placeholder={ts("select_buyer_type")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: tr("purchaser"), value: "trader" },
            { label: tr("customers"), value: "customer" },
          ]}
        />
      </Form.Item>
      <Form.Item name="customer_code" label={ts("customer")}>
        <InfiniteScrollSelect
          queryKey={["customers-select"]}
          fetchFn={getCustomers}
          mapOption={(item: any) => ({
            label: `${item.customer_name} (${item.customer_code})`,
            value: item.customer_code,
          })}
          placeholder={ts("select_customer")}
          style={{ width: 250 }}
        />
      </Form.Item>
      <Form.Item name="order_source_type" label={ts("source")}>
        <Select
          placeholder={ts("select_source")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: ts("purchase_order.transaction_ticket"), value: "transaction_ticket" },
            { label: ts("warehouse"), value: "warehouse" },
            { label: ts("product_lot"), value: "product_lot" },
          ]}
        />
      </Form.Item>
      <Form.Item name="status" label={ts("status")}>
        <Select
          placeholder={ts("select_status")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: tc("all"), value: "all" },
            { label: tst("draft"), value: "draft" },
            { label: tst("approved"), value: "approved" },
            { label: tst("allocated"), value: "allocated" },
            { label: tst("delivering"), value: "shipping" },
            { label: tst("closed"), value: "closed" },
            { label: tst("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default OrderFilter;
