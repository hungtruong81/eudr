"use client";

import { useQuery } from "@tanstack/react-query";
import {
  Button,
  Card,
  Col,
  Empty,
  Row,
  Space,
  Spin,
  Table,
  Tabs,
  Tag,
  Typography,
} from "antd";
import { getLandById } from "../actions";

import { useTranslations } from "next-intl";

const { Title, Text } = Typography;

interface Props {
  plot_code: string;
}

const LandDetailClient = ({ plot_code }: Props) => {
  const t = useTranslations("ManageLand.Land");
  const tCommon = useTranslations("Common");

  const detailColumns = [
    {
      title: t("general_info"),
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

  const { data, isLoading, error } = useQuery({
    queryKey: ["land-detail", plot_code],
    queryFn: () => getLandById(plot_code),
    enabled: !!plot_code,
  });

  const land = data?.data;

  if (isLoading)
    return (
      <div className="flex justify-center p-20">
        <Spin description={t("loading_info")} />
      </div>
    );
  if (error || !land)
    return <Empty description={t("not_found")} />;

  const tabItems = [
    {
      key: "1",
      label: t("general_info"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("owner_registration")}</Text>}
            dataSource={[
              { label: t("farmer_name"), value: land.farmer_name },
              { label: t("phone"), value: land.phone },
              { label: t("email"), value: land.email },
              { label: tCommon("company"), value: land.company_name },
              { label: t("ownership"), value: land.ownership },
              { label: t("plot_name"), value: land.plot_name },
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
      label: t("technical_cultivation"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("technical_specs")}</Text>}
            dataSource={[
              { label: t("actual_area"), value: `${land.land_area} ha` },
              { label: t("area_24h"), value: `${land.area_24} ha` },
              { label: t("plant_type"), value: land.plant_type },
              { label: t("crop_type"), value: land.crop_type },
              { label: t("planting_year"), value: land.year_of_planting },
              {
                label: t("max_yield"),
                value: `${land.maximum_yield?.toLocaleString()} kg`,
              },
              { label: t("soil_type"), value: land.soil },
              { label: t("classification"), value: land.classify },
              { label: tCommon("status"), value: land.status },
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
      label: t("location_geography"),
      children: (
        <Card>
          <Table
            title={() => <Text strong>{t("detailed_geography")}</Text>}
            dataSource={[
              { label: tCommon("address"), value: land.address },
              {
                label: t("province"),
                value: `${land.province_name} (ID: ${land.province_id})`,
              },
              {
                label: t("zone"),
                value: `${land.zone_name} (ID: ${land.zone_id})`,
              },
              { label: t("country"), value: land.country },
              {
                label: t("altitude"),
                value: `${land.altitude_above_sea_level} m`,
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
      key: "4",
      label: t("gps_coordinates"),
      children: (
        <Row gutter={16}>
          <Col span={12}>
            <Table
              title={() => <strong>{t("coordinate_list")}</strong>}
              dataSource={land.coordinates}
              pagination={{ pageSize: 8 }}
              size="small"
              columns={[
                { title: t("latitude"), dataIndex: "lat" },
                { title: t("longitude"), dataIndex: "lng" },
              ]}
              rowKey={(r, i) => `coord-${i}`}
            />
          </Col>
          <Col span={12}>
            <Table
              title={() => <strong>{t("origin_points")}</strong>}
              dataSource={land.coordinate_origin_points}
              pagination={false}
              size="small"
              columns={[
                { title: t("coordinate_x"), dataIndex: "x" },
                { title: t("coordinate_y"), dataIndex: "y" },
              ]}
              rowKey={(r, i) => `origin-${i}`}
            />
          </Col>
        </Row>
      ),
    },
    {
      key: "5",
      label: t("records_notes"),
      children: (
        <Card>
          <Table
            dataSource={[
              {
                label: t("records"),
                value: (
                  <div className="flex gap-2">
                    {Object.entries(land.land_records).map(([key, value]) => (
                      <Button
                        key={key}
                        type="link"
                        onClick={() => {
                          window.open(value as string, "_blank");
                        }}>
                        {key}
                      </Button>
                    ))}
                  </div>
                ),
              },
              {
                label: tCommon("notes"),
                value: (
                  <Text type="danger">
                    {land.notes || tCommon("no_notes")}
                  </Text>
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
  ];

  return (
    <div className="">
      {/* Header gọn nhẹ */}
      <div className="flex justify-between items-center mb-6">
        <Space orientation="vertical" size={0}>
          <Title level={5} className="!m-0">
            {land.plot_name}
          </Title>
        </Space>
        <Space size="middle">
          <Tag
            color={land.is_approved === 1 ? "green" : "orange"}
            className="px-4 py-1">
            {land.is_approved === 1 ? t("approved").toUpperCase() : t("wait_approve").toUpperCase()}
          </Tag>
          <Tag
            color={land.eudr_status === 1 ? "blue" : "red"}
            className="px-4 py-1">
            EUDR: {land.eudr_status === 1 ? t("valid").toUpperCase() : t("invalid").toUpperCase()}
          </Tag>
        </Space>
      </div>

      <Tabs defaultActiveKey="1" items={tabItems} />
    </div>
  );
};

export default LandDetailClient;
