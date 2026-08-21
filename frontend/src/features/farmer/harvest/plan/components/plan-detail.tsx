"use client";

import { useQuery } from "@tanstack/react-query";
import { Card, Tabs, Tag, Typography, Table, Empty, Spin, Space } from "antd";
import { getPlanById } from "../actions";
import { IHarvestPlan } from "../types";
import { useTranslations } from "next-intl";

const { Title, Text } = Typography;

interface Props {
  harvest_plan_code: string;
}

const HarvestPlanDetailClient = ({ harvest_plan_code }: Props) => {
  const t = useTranslations("Harvest.Plan");

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
    queryKey: ["harvest-plan-detail", harvest_plan_code],
    queryFn: () => getPlanById(harvest_plan_code),
    enabled: !!harvest_plan_code,
  });

  // Tùy thuộc vào cấu trúc ApiResponseList thực tế, giả sử data.data chứa đối tượng hoặc mảng
  const plan: IHarvestPlan = Array.isArray(data?.data)
    ? data?.data[0]
    : data?.data;

  if (isLoading)
    return (
      <div className="flex justify-center p-20">
        <Spin
          description={t("loading_plan")}
          size="large"
        />
      </div>
    );

  if (error || !plan)
    return <Empty description={t("plan_not_found")} />;

  // Cột cho bảng danh sách lô đất (Lands)
  const landColumns = [
    {
      title: t("plot_code"),
      dataIndex: "plot_code",
      key: "plot_code",
      render: (text: string) => <Tag color="blue">{text.toUpperCase()}</Tag>,
    },
    {
      title: t("plot_name"),
      dataIndex: "plot_name",
      key: "plot_name",
    },
  ];

  const tabItems = [
    {
      key: "1",
      label: t("general_info"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("id_contract")}</Text>}
            dataSource={[
              {
                label: t("harvest_plan_code"),
                value: plan.harvest_plan_code.toUpperCase(),
              },
              {
                label: t("contract_code"),
                value: plan.contract_code?.toUpperCase(),
              },
              { label: t("farmer_owner"), value: plan.farmer_name },
              {
                label: t("system_created_at"),
                value: plan.created_at
                  ? new Date(plan.created_at).toLocaleString("vi-VN")
                  : "-",
              },
              { label: t("notes"), value: plan.notes || t("no_notes") },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
          />
        </Card>
      ),
    },
    {
      key: "2",
      label: t("harvest_info"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("plan_yield")}</Text>}
            dataSource={[
              {
                label: t("start_date"),
                value: plan.harvest_start_date
                  ? new Date(plan.harvest_start_date).toLocaleDateString(
                      "vi-VN",
                    )
                  : "-",
              },
              {
                label: t("end_date"),
                value: plan.harvest_end_date
                  ? new Date(plan.harvest_end_date).toLocaleDateString("vi-VN")
                  : "-",
              },
              {
                label: t("tapping_regime_label"),
                value: <Tag color="cyan">{plan.tapping_regime}</Tag>,
              },
              { label: t("total_schedules"), value: plan.schedule_count },
              { label: t("harvest_count"), value: plan.harvest_count },
              {
                label: t("expected_yield"),
                value: plan.expected_yield
                  ? `${Number(plan.expected_yield).toLocaleString("vi-VN")} kg`
                  : "-",
              },
              {
                label: t("actual_yield"),
                value: (
                  <span className="font-semibold text-green-600">
                    {plan.actual_yield
                      ? `${plan.actual_yield.toLocaleString("vi-VN")} kg`
                      : "0 kg"}
                  </span>
                ),
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
          />
        </Card>
      ),
    },
    {
      key: "3",
      label: `${t("plot_list")} (${plan.lands?.length || 0})`,
      children: (
        <Card title={t("plots_applied")}>
          <Table
            dataSource={plan.lands}
            columns={landColumns}
            rowKey="plot_id"
            pagination={false}
            bordered
            locale={{ emptyText: t("no_plot_data") }}
          />
        </Card>
      ),
    },
  ];

  return (
    <div className="">
      <div className="flex justify-between items-center mb-6">
        <Space orientation="vertical" size={0}>
          <Title level={5} className="!m-0">
            {t("detail_title")}: {plan.harvest_plan_code.toUpperCase()}
          </Title>
          <Typography.Text type="secondary">
            {t("farmer_owner")}: {plan.farmer_name}
          </Typography.Text>
        </Space>

        <Space size="middle">
          <Tag
            color={plan.eudr_status === 1 ? "green" : "red"}
            className="px-4 py-1 uppercase font-semibold">
            EUDR: {plan.eudr_status === 1 ? "Eudr" : "Non Eudr"}
          </Tag>
        </Space>
      </div>

      <Tabs defaultActiveKey="1" items={tabItems} />
    </div>
  );
};

export default HarvestPlanDetailClient;
