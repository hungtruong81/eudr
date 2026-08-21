"use client";
import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { IGerRawMaterialReleaseParams } from "../actions";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface IRawMaterialReleaseFilterProps {
  onSearch: (params: Partial<IGerRawMaterialReleaseParams>) => void;
}

const RawMaterialReleaseFilter = ({
  onSearch,
}: IRawMaterialReleaseFilterProps) => {
  const t = useTranslations("Factory.material_release");
  const tc = useTranslations("Common");
  const ts = useTranslations("Status");
  
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
      created_date_from: dateRange?.[0]?.format("YYYY-MM-DD") || "",
      created_date_to: dateRange?.[1]?.format("YYYY-MM-DD") || "",
    });
  };

  const handleReset = () => {
    form.resetFields();
    onSearch({
      created_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      created_date_to: dayjs().format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tc("search")}>
        <Input placeholder={tc("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="status" className="mb-0" label={tc("status")} initialValue="all">
        <Select
          placeholder={tc("status")}
          style={{ width: 160 }}
          allowClear
          options={[
            { label: tc("all"), value: "all" },
            { label: ts("pending"), value: "pending" },
            { label: ts("approved"), value: "approved" },
            { label: t("rejected"), value: "rejected" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={t("created_date")}>
        <RangePicker
          placeholder={[tc("from_date"), tc("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default RawMaterialReleaseFilter;
