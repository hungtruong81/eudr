import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { rangePresets } from "@/constants/range-date";
import { getCustomers } from "@/features/sale/customer/actions";
import { Form, DatePicker, Select } from "antd";
import { useTranslations } from "next-intl";
import React from "react";

const { RangePicker } = DatePicker;

interface PurchaseOrderFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const PurchaseOrderFilter = ({
  onSearch,
  onReset,
  loading,
}: PurchaseOrderFilterProps) => {
  const t = useTranslations("Sales.purchase_order");
  const tCommon = useTranslations("Common");
  const tStatus = useTranslations("Status");
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
      <Form.Item name="date_range" label={t("date_range")}>
        <RangePicker
          presets={rangePresets}
          format="DD/MM/YYYY"
          placeholder={[tCommon("from_date"), tCommon("to_date")]}
        />
      </Form.Item>
      <Form.Item name="customer_code" label={t("customer")}>
        <InfiniteScrollSelect
          queryKey={["customers-select"]}
          fetchFn={getCustomers}
          mapOption={(item: any) => ({
            label: `${item.customer_name} (${item.customer_code})`,
            value: item.customer_code,
          })}
          placeholder={t("select_customer")}
          style={{ width: 250 }}
        />
      </Form.Item>
      <Form.Item name="order_source_type" label={t("source")}>
        <Select
          placeholder={t("select_source")}
          allowClear
          style={{ width: 170 }}
          options={[
            { label: t("transaction_ticket"), value: "transaction_ticket" },
            { label: t("warehouse"), value: "warehouse" },
            { label: t("product_lot"), value: "product_lot" },
          ]}
        />
      </Form.Item>
      <Form.Item name="status" label={t("status")}>
        <Select
          placeholder={t("select_status")}
          allowClear
          style={{ width: 150 }}
          options={[
            { label: tStatus("all"), value: "all" },
            { label: tStatus("draft"), value: "draft" },
            { label: tStatus("approved"), value: "approved" },
            { label: tStatus("allocated"), value: "allocated" },
            { label: tStatus("delivering"), value: "shipping" },
            { label: tStatus("closed"), value: "closed" },
            { label: tStatus("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default PurchaseOrderFilter;
