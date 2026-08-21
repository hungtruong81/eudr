"use client";

import BaseSheet from "@/components/shared/base-sheet";
import React, { useEffect, useState } from "react";
import {
  Form,
  InputNumber,
  Input,
  message,
  Tag,
  Table,
  Typography,
  DatePicker,
} from "antd";
import { ISchedule, IScheduleById } from "../../plan/types";
import { getScheduleById, getSchedules } from "../../plan/actions";
import { createOrUpdateHarvest, updateHarvestScheduleDate } from "../actions";
import { handleApiError } from "@/lib/api-error";
import { useQuery } from "@tanstack/react-query";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IHarvestSheetProps {
  open: boolean;
  onClose: () => void;
  schedule: ISchedule | null;
  onRefresh?: () => void;
}

const HarvestSheet = ({
  open,
  onClose,
  schedule,
  onRefresh,
}: IHarvestSheetProps) => {
  const t = useTranslations("Harvest.Result");
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const detailColumns = [
    {
      title: t("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: t("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const { data: scheduleDetail, isLoading: isFetchingDetail } = useQuery({
    queryKey: ["harvest-schedule-detail", schedule?.harvest_schedule_code],
    queryFn: () => getScheduleById(schedule!.harvest_schedule_code),
    enabled: !!schedule?.harvest_schedule_code && open,
  });

  const { data: allSchedulesData } = useQuery({
    queryKey: ["harvest-schedules-shifting", schedule?.harvest_plan_code],
    queryFn: () =>
      getSchedules({
        harvest_plan_code: schedule?.harvest_plan_code,
        page: 1,
        limit: 1000,
      }),
    enabled: !!schedule?.harvest_plan_code && open,
  });

  useEffect(() => {
    if (open && scheduleDetail?.data) {
      const data = scheduleDetail.data as unknown as IScheduleById;
      form.setFieldsValue({
        pickup_date: dayjs(data.pickup_date),
        actual_yield: data.actual_yield,
        notes: data.notes,
      });
    } else if (!open) {
      form.resetFields();
    }
  }, [open, scheduleDetail, form]);

  const handleSubmit = async (values: any) => {
    if (!schedule?.harvest_schedule_code) return;

    try {
      setLoading(true);

      const newDate = values.pickup_date;
      const originalDate = dayjs(schedule.pickup_date);

      if (!newDate.isSame(originalDate, "day")) {
        const schedules = allSchedulesData?.data?.records || [];
        const tappingRegime = schedule.tapping_regime || "D1";
        const stepDays = parseInt(tappingRegime.replace("D", "")) || 1;

        const currentIndex = schedules.findIndex(
          (s) => s.harvest_schedule_code === schedule.harvest_schedule_code,
        );

        if (currentIndex !== -1) {
          const updatedSchedules = schedules.map((s, index) => {
            if (index < currentIndex) return s;

            const diffDays = (index - currentIndex) * stepDays;
            const shiftedDate = newDate.add(diffDays, "day");

            return {
              ...s,
              pickup_date: shiftedDate.format("YYYY-MM-DD"),
              pickup_time: s.pickup_time
                ? dayjs(s.pickup_time, "HH:mm:ss").format("HH:mm")
                : "00:00",
              expected_yield: Number(s.expected_yield),
            };
          });

          await updateHarvestScheduleDate({
            harvest_plan_code: schedule.harvest_plan_code,
            schedules: updatedSchedules.map((s) => ({
              plot_id: s.plot_id,
              pickup_date: s.pickup_date,
              pickup_time: s.pickup_time as string,
              expected_yield: Number(s.expected_yield),
            })),
          });
        }
      }

      await createOrUpdateHarvest({
        harvest_schedule_code: schedule.harvest_schedule_code,
        actual_yield: values.actual_yield,
      });

      message.success(t("update_success"));
      if (onRefresh) onRefresh();
      onClose();
    } catch (error) {
      handleApiError(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      loading={loading || isFetchingDetail}
      title={t("update_harvest")}>
      <Form form={form} layout="vertical" onFinish={handleSubmit}>
        {schedule && (
          <Table
            dataSource={[
              {
                label: t("schedule_code"),
                value: schedule.harvest_schedule_code.toUpperCase(),
              },
              { label: t("plot"), value: schedule.plot_name },
              {
                label: t("tapping_regime_label"),
                value: <Tag color="cyan">{schedule.tapping_regime}</Tag>,
              },
              {
                label: t("expected_yield"),
                value: `${Number(schedule.expected_yield).toLocaleString("vi-VN")} kg`,
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
            className="mb-6"
          />
        )}

        <Form.Item
          name="pickup_date"
          label={t("harvest_date")}
          rules={[{ required: true, message: t("select_harvest_date_msg") }]}>
          <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
        </Form.Item>

        <Form.Item
          name="actual_yield"
          label={t("actual_yield")}
          rules={[
            { required: true, message: t("enter_actual_yield_msg") },
          ]}>
          <InputNumber style={{ width: "100%" }} placeholder={t("enter_kg")} />
        </Form.Item>

        <Form.Item name="notes" label={t("notes")}>
          <Input.TextArea rows={4} placeholder={t("enter_notes")} />
        </Form.Item>
      </Form>
    </BaseSheet>
  );
};

export default HarvestSheet;
