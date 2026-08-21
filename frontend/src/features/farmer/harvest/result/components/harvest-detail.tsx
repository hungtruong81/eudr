"use client";

import { useQuery } from "@tanstack/react-query";
import { Card, Empty, Space, Spin, Table, Tag, Typography } from "antd";
import { getScheduleById } from "../../plan/actions";
import { IScheduleById } from "../../plan/types";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";

const { Title, Text } = Typography;

interface Props {
  harvest_schedule_code: string;
}

const HarvestDetail = ({ harvest_schedule_code }: Props) => {
  const t = useTranslations("Harvest.Result");

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

  const { data, isLoading, error } = useQuery({
    queryKey: ["harvest-schedule-detail", harvest_schedule_code],
    queryFn: () => getScheduleById(harvest_schedule_code),
    enabled: !!harvest_schedule_code,
  });

  const schedule: IScheduleById = Array.isArray(data?.data)
    ? data?.data[0]
    : data?.data;

  if (isLoading)
    return (
      <div className="flex justify-center p-20">
        <Spin
          description={t("loading_result")}
          size="large"
        />
      </div>
    );

  if (error || !schedule)
    return <Empty description={t("result_not_found")} />;

  return (
    <div className="">
      <div className="flex justify-between items-center mb-6">
        <Space orientation="vertical" size={0}>
          <Title level={5} className="!m-0">
            {t("detail_title")}: {schedule.harvest_schedule_code.toUpperCase()}
          </Title>
          <Typography.Text type="secondary">
            {t("plan_code")}: {schedule.harvest_plan_code.toUpperCase()}
          </Typography.Text>
        </Space>

        <Space size="middle">
          <Tag color="blue" className="px-4 py-1 uppercase font-semibold">
            {schedule.plot_name}
          </Tag>
        </Space>
      </div>

      <Card>
        <Table
          dataSource={[
            {
              label: t("schedule_code"),
              value: schedule.harvest_schedule_code.toUpperCase(),
            },
            {
              label: t("plan_code"),
              value: schedule.harvest_plan_code.toUpperCase(),
            },
            {
              label: t("harvest_date"),
              value: dayjs(schedule.pickup_date).format("DD/MM/YYYY"),
            },
            { label: t("harvest_time"), value: schedule.pickup_time },
            { label: t("plot"), value: schedule.plot_name },
            {
              label: t("expected_yield"),
              value: `${Number(schedule.expected_yield).toLocaleString("vi-VN")} kg`,
            },
            {
              label: t("actual_yield"),
              value: (
                <span className="font-semibold text-green-600">
                  {schedule.actual_yield.toLocaleString("vi-VN")} kg
                </span>
              ),
            },
            { label: t("notes"), value: schedule.notes || t("no_notes") },
          ]}
          columns={detailColumns}
          pagination={false}
          size="small"
          bordered
          rowKey="label"
        />
      </Card>
    </div>
  );
};

export default HarvestDetail;
