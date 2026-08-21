import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";

const { RangePicker } = DatePicker;

interface IThuMuaFilterProps {
  onSearch: (params: any) => void;
}

const ThuMuaFilter = ({ onSearch }: IThuMuaFilterProps) => {
  const [form] = Form.useForm();

  const handleFinish = (values: any) => {
    const { dateRange, ...rest } = values;
    onSearch({
      ...rest,
      start_date: dateRange?.[0]?.format("YYYY-MM-DD") || "",
      end_date: dateRange?.[1]?.format("YYYY-MM-DD") || "",
    });
  };

  const handleReset = () => {
    form.resetFields();
    onSearch({
      search: "",
      status: "all",
      start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      end_date: dayjs().format("YYYY-MM-DD"),
    });
  };

  useEffect(() => {
    form.setFieldsValue({
      dateRange: [dayjs().subtract(1, "month"), dayjs()],
    });
  }, [form]);

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label="Tìm kiếm">
        <Input placeholder="Nhập mã phiếu, người bán..." allowClear />
      </Form.Item>
      <Form.Item
        name="status"
        className="mb-0"
        initialValue="all"
        label="Trạng thái">
        <Select
          placeholder="Chọn trạng thái"
          style={{ width: 160 }}
          options={[
            { label: "Tất cả", value: "all" },
            { label: "Chờ xác nhận", value: "pending" },
            { label: "Đã xác nhận", value: "confirmed" },
            { label: "Đã hủy", value: "cancelled" },
            { label: "Đã hoàn thành", value: "completed" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label="Ngày tạo">
        <RangePicker
          placeholder={["Từ ngày", "Đến ngày"]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default ThuMuaFilter;
