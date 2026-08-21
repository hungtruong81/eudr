"use client";

import { useQuery } from "@tanstack/react-query";
import { Card, Empty, Space, Spin, Table, Tabs, Tag, Typography } from "antd";
import { getPlantById } from "../actions";
const { Title, Text } = Typography;

import { useTranslations } from "next-intl";

interface Props {
  plant_code: string;
}

const PlantDetailClient = ({ plant_code }: Props) => {
  const t = useTranslations("ManageLand.Plant");
  const tCommon = useTranslations("Common");

  const { data, isLoading, error } = useQuery({
    queryKey: ["plant-detail", plant_code],
    queryFn: () => getPlantById(plant_code),
    enabled: !!plant_code,
  });

  const detailColumns = [
    {
      title: tCommon("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tCommon("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const plant = Array.isArray(data?.data) ? data?.data[0] : data?.data;

  if (isLoading)
    return (
      <div className="flex justify-center p-20">
        <Spin description={t("loading_info")} size="large" />
      </div>
    );

  if (error || !plant) return <Empty description={t("not_found")} />;

  const tabItems = [
    {
      key: "1",
      label: t("general_info"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("identification_attachment")}</Text>}
            dataSource={[
              { label: t("plant_code"), value: plant.plant_code.toUpperCase() },
              { label: t("plot_code"), value: plant.plot_code.toUpperCase() },
              { label: t("plot_name"), value: plant.plot_name },
              { label: t("plantation_name"), value: plant.plantation_name },
              {
                label: t("system_created_at"),
                value: plant.created_at
                  ? new Date(plant.created_at).toLocaleDateString("vi-VN")
                  : "-",
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
      key: "2",
      label: t("cultivation_ecology"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("technical_specs_cultivation")}</Text>}
            dataSource={[
              { label: t("crop_type_label"), value: plant.crop_type },
              { label: t("plantation_type"), value: plant.type_of_plantation },
              { label: t("year_of_planting"), value: plant.year_of_planting },
              {
                label: t("date_end_planting"),
                value: plant.date_end_of_planting,
              },
              { label: t("clone_type_label"), value: plant.clone_type_of_tree },
              { label: t("planting_method"), value: plant.planting_method },
              { label: t("planting_distance"), value: plant.planting_distance },
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
      label: t("harvest_yield"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("tapping_harvest_info")}</Text>}
            dataSource={[
              {
                label: t("year_of_start_tapping"),
                value: plant.year_of_start_tapping,
              },
              {
                label: t("year_upward_tapping"),
                value: plant.year_of_upward_tapping,
              },
              {
                label: t("tapping_method_detail"),
                value: plant.tapping_method,
              },
              {
                label: t("density_tapping"),
                value: plant.denity_of_tapping_tree
                  ? `${plant.denity_of_tapping_tree} cây/ha`
                  : null,
              },
              {
                label: t("density_effective"),
                value: plant.effective_tree_density
                  ? `${plant.effective_tree_density} cây/ha`
                  : null,
              },
              {
                label: t("standard_perimeter_rate"),
                value: plant.percentage_of_trees_meeting_perimeter_standards,
              },
              { label: t("expected_harvest"), value: plant.expected_harvest },
              {
                label: t("annual_yield"),
                value: plant.annual_yield
                  ? `${plant.annual_yield.toLocaleString()} kg`
                  : null,
              },
              { label: t("production_24"), value: plant.production_24 },
              {
                label: t("standard_deviation"),
                value: plant.standard_deviation,
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
  ];

  const getStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
      case "active":
        return "green";
      case "harvesting":
        return "blue";
      case "inactive":
        return "red";
      default:
        return "default";
    }
  };

  return (
    <div className="">
      <div className="flex justify-between items-center mb-6">
        <Space orientation="vertical" size={0}>
          <Title level={5} className="!m-0">
            {t("plant_detail_title")}: {plant.plant_code.toUpperCase()}
          </Title>
          <Typography.Text type="secondary">
            {t("land_plot")}: {plant.plot_name}
          </Typography.Text>
        </Space>

        <Space size="middle">
          <Tag
            color={getStatusColor(plant.plant_status)}
            className="px-4 py-1 uppercase font-semibold">
            {tCommon("status")}: {plant.plant_status || t("status_unknown")}
          </Tag>
        </Space>
      </div>

      <Tabs defaultActiveKey="1" items={tabItems} />
    </div>
  );
};

export default PlantDetailClient;
