import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { IGetExternalProductLotParams } from "../actions";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface IExternalProductLotFilterProps {
  onSearch: (params: Partial<IGetExternalProductLotParams>) => void;
}

const ExternalProductLotFilter = ({
  onSearch,
}: IExternalProductLotFilterProps) => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");
  const tStatus = useTranslations("Status");
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
      search: "",
      production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      production_date_to: dayjs().format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tCommon("search")}>
        <Input placeholder={tCommon("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item
        name="status"
        className="mb-0"
        label={tCommon("status")}
        initialValue="all">
        <Select
          placeholder={tCommon("status")}
          style={{ width: 160 }}
          allowClear
          options={[
            { label: tStatus("all"), value: "all" },
            { label: tStatus("draft"), value: "draft" },
            { label: tStatus("confirmed"), value: "confirmed" },
            { label: tStatus("shipped"), value: "shipped" },
            { label: tStatus("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={t("production_date") || tCommon("production_date")}>
        <RangePicker
          placeholder={[tCommon("from_date"), tCommon("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default ExternalProductLotFilter;
