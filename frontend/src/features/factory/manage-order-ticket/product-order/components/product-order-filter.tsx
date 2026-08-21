"use client";
import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { IGetProductOrderParams } from "../actions";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface IProductOrderFilterProps {
  onSearch: (params: Partial<IGetProductOrderParams>) => void;
}

const ProductOrderFilter = ({ onSearch }: IProductOrderFilterProps) => {
  const t = useTranslations("Factory.product_order");
  const tc = useTranslations("Common");
  
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
      status: "all",
      production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      production_date_to: dayjs().format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tc("search")}>
        <Input placeholder={tc("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="status" className="mb-0" label={t("status")}>
        <Select
          placeholder={t("status")}
          style={{ width: 160 }}
          allowClear
          options={[
            { label: t("approved"), value: "approved" },
            { label: t("in_production"), value: "in_production" },
            { label: t("completed"), value: "completed" },
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

export default ProductOrderFilter;
