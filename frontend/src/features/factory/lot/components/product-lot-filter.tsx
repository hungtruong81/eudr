import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";
import { rangePresets } from "@/constants/range-date";
import { IGetProductLotParams } from "../actions";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getProductionOrders } from "../../manage-order-ticket/product-order/actions";
import { IProductionOrder } from "../../manage-order-ticket/product-order/types";

const { RangePicker } = DatePicker;

interface IProductLotFilterProps {
  onSearch: (params: Partial<IGetProductLotParams>) => void;
}

const ProductLotFilter = ({ onSearch }: IProductLotFilterProps) => {
  const [form] = Form.useForm();

  useEffect(() => {
    form.setFieldsValue({
      dateRange: [dayjs().subtract(1, "month"), dayjs()],
    });
  }, [form]);

  const handleFinish = (values: any) => {
    const { dateRange, ...rest } = values;
    onSearch({
      ...rest,
      production_date_from: dateRange?.[0]?.format("YYYY-MM-DD") || "",
      production_date_to: dateRange?.[1]?.format("YYYY-MM-DD") || "",
    });
  };

  const handleReset = () => {
    form.resetFields();
    onSearch({
      page: 1,
      limit: 12,
      status: "all",
      lot_type: "all",
      search: "",
      production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      production_date_to: dayjs().format("YYYY-MM-DD"),
    });
  };

  const t = useTranslations("Factory");
  const ts = useTranslations("Status"); // translation hook for status labels
  const tc = useTranslations("Common");

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tc("search")}>
        <Input placeholder={t("search_lot_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="production_order_id" label={t("production_order")}>
        <InfiniteScrollSelect<IProductionOrder>
          queryKey={["production-orders"]}
          fetchFn={getProductionOrders}
          mapOption={(record) => ({
            label: record.production_order_name,
            value: String(record.production_order_id),
          })}
          style={{ width: 200 }}
        />
      </Form.Item>
      <Form.Item name="status" className="mb-0" label={tc("status")} initialValue="all">
        <Select
          placeholder={tc("status")}
          style={{ width: 160 }}
          allowClear
          options={[
            { label: tc("all"), value: "all" },
            { label: ts("available"), value: "available" },
            { label: ts("allocated"), value: "allocated" },
            { label: ts("shipped"), value: "shipped" },
            { label: ts("defective"), value: "defective" },
          ]}
        />
      </Form.Item>
      <Form.Item name="lot_type" className="mb-0" label={t("lot_type")} initialValue="all">
        <Select
          placeholder={t("lot_type")}
          style={{ width: 160 }}
          allowClear
          options={[
            { label: tc("all"), value: "all" },
            { label: t("internal"), value: "internal" },
            { label: t("external"), value: "external" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={t("production_date")}>
        <RangePicker
          placeholder={[tc("from_date"), tc("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default ProductLotFilter;
