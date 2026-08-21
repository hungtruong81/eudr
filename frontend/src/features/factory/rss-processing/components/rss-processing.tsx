"use client";

import type { ReactNode } from "react";
import { useCallback, useEffect, useMemo, useState } from "react";
import {
  Space,
  Button,
  Flex,
  Tag,
  Card,
  Row,
  Col,
  Spin,
  Typography,
  Tabs,
} from "antd";
import { ArrowLeftOutlined } from "@ant-design/icons";
import { TankStation } from "./stations/tank-station";
import { TroughStation } from "./stations/trough-station";
import { TrolleyStation } from "./stations/trolley-station";
import { DryingStation } from "./stations/drying-station";
import { BalingStation } from "./stations/baling-station";
import { ITransportationRoute } from "@/features/route/transportation-route/types";
import {
  BaleDraft,
  CutBatch,
  DriedPole,
  RSS_CUT_EYE_OPTIONS,
  RSS_OVEN_OPTIONS,
  RSS_POLE_OPTIONS,
  RSS_SAMPLE_WORKFLOW_PLANS,
  RSS_TROUGH_OPTIONS,
  RolledBatch,
  RssQuality,
  RssWorkflowPlan,
  TrolleyBatch,
  TroughBatch,
  createRssProcessStateFromPlan,
} from "./shared/rss-process-state";
import { useRouter } from "nextjs-toploader/app";
import { useQuery } from "@tanstack/react-query";
import { getProductionOrders } from "../../manage-order-ticket/product-order/actions";
import { IProductionOrder } from "../../manage-order-ticket/product-order/types";

const { Text } = Typography;

interface RssProcessingProps {
  transportationRoute?: ITransportationRoute;
  onBack?: () => void;
}

interface StationItem {
  key: string;
  label: string;
  shortLabel: string;
  description: string;
  stat: string;
  children: ReactNode;
}

const qualityCycle: RssQuality[] = ["L1", "L2", "L3"];

const formatKg = (value?: number) =>
  new Intl.NumberFormat("vi-VN", {
    maximumFractionDigits: 0,
  }).format(value ?? 0);

const getStatusColor = (status?: string) => {
  switch (status) {
    case "approved":
      return "blue";
    case "in_production":
      return "orange";
    case "completed":
      return "green";
    default:
      return "default";
  }
};

const getStatusLabel = (status?: string) => {
  switch (status) {
    case "approved":
      return "Đã duyệt";
    case "in_production":
      return "Đang sản xuất";
    case "completed":
      return "Hoàn tất";
    default:
      return status ?? "Mẫu";
  }
};

const getWorkflowPlanFromOrder = (
  order: IProductionOrder,
  index: number,
): RssWorkflowPlan => {
  const quantity = Number(order.required_quantity || 600);
  const troughCount = Math.min(6, Math.max(2, Math.ceil(quantity / 400)));
  const poleCount = Math.min(8, Math.max(2, Math.ceil(quantity / 250)));

  const startIndex =
    Number(order.production_order_id) % RSS_TROUGH_OPTIONS.length;

  const quality = qualityCycle[index % qualityCycle.length];

  const troughCodes = Array.from(
    { length: troughCount },
    (_, itemIndex) =>
      RSS_TROUGH_OPTIONS[(startIndex + itemIndex) % RSS_TROUGH_OPTIONS.length],
  );

  const cutEyeCodes = Array.from(
    { length: troughCount },
    (_, itemIndex) =>
      RSS_CUT_EYE_OPTIONS[
        (startIndex + itemIndex) % RSS_CUT_EYE_OPTIONS.length
      ],
  );

  const poleCodes = Array.from(
    { length: poleCount },
    (_, itemIndex) =>
      RSS_POLE_OPTIONS[(startIndex + itemIndex) % RSS_POLE_OPTIONS.length],
  );

  return {
    id: String(order.production_order_id),
    orderCode: order.production_order_code,
    orderName: order.production_order_name,
    productName: order.product_type_name,
    productionDate: order.production_date,
    status: order.status,
    requiredQuantityKg: quantity,
    tankCode: `Bồn ${(startIndex % 4) + 1}`,
    trolleyCode: `GG-${String((startIndex % 6) + 1).padStart(2, "0")}`,
    ovenCode: RSS_OVEN_OPTIONS[startIndex % RSS_OVEN_OPTIONS.length],
    troughCodes,
    cutEyeCodes,
    poleCodes,
    quality,
    defaultVolumeLiters: Math.max(160, Math.round(quantity / troughCount / 2)),
    defaultSheetCount: Math.max(80, Math.round(quantity / troughCount / 3)),
  };
};

const RssProcessing = ({ onBack }: RssProcessingProps) => {
  const router = useRouter();

  const [activeTab, setActiveTab] = useState<string | null>(null);

  const [selectedWorkflowPlanId, setSelectedWorkflowPlanId] = useState<
    string | null
  >(null);

  const [processState, setProcessState] = useState(() =>
    createRssProcessStateFromPlan(RSS_SAMPLE_WORKFLOW_PLANS[0]),
  );

  const { data: productionOrdersData, isFetching: isFetchingOrders } = useQuery(
    {
      queryKey: ["rss-processing-production-orders"],
      queryFn: () => getProductionOrders({ page: 1, limit: 8, status: "all" }),
    },
  );

  const workflowPlans = useMemo(() => {
    const orders = productionOrdersData?.data?.records ?? [];

    if (orders.length === 0) return RSS_SAMPLE_WORKFLOW_PLANS;

    return orders.map(getWorkflowPlanFromOrder);
  }, [productionOrdersData]);

  const selectedWorkflowPlan = useMemo(() => {
    if (!selectedWorkflowPlanId) return null;

    return (
      workflowPlans.find((plan) => plan.id === selectedWorkflowPlanId) ?? null
    );
  }, [selectedWorkflowPlanId, workflowPlans]);

  useEffect(() => {
    if (!selectedWorkflowPlan) return;

    setProcessState(createRssProcessStateFromPlan(selectedWorkflowPlan));
    setActiveTab(null);
  }, [selectedWorkflowPlan]);

  const handleCreateTroughBatches = useCallback((batches: TroughBatch[]) => {
    setProcessState((prev) => ({
      ...prev,
      troughBatches: [...prev.troughBatches, ...batches],
    }));
  }, []);

  const handleCreateCutBatch = useCallback((batch: CutBatch) => {
    setProcessState((prev) => ({
      ...prev,
      cutBatches: [batch, ...prev.cutBatches],
    }));
  }, []);

  const handleCreateRolledBatch = useCallback((batch: RolledBatch) => {
    setProcessState((prev) => ({
      ...prev,
      rolledBatches: [batch, ...prev.rolledBatches],
    }));
  }, []);

  const handleCreateTrolleyBatch = useCallback((batch: TrolleyBatch) => {
    setProcessState((prev) => ({
      ...prev,
      trolleyBatches: [batch, ...prev.trolleyBatches],
    }));
  }, []);

  const handleMoveTrolleyToOven = useCallback(
    (trolleyCode: string, ovenCode: string) => {
      setProcessState((prev) => ({
        ...prev,
        trolleyBatches: prev.trolleyBatches.map((batch) =>
          batch.trolleyCode === trolleyCode
            ? { ...batch, status: "drying", ovenCode }
            : batch,
        ),
      }));
    },
    [],
  );

  const handleCreateDriedPoles = useCallback(
    (trolleyCode: string, ovenCode: string, poles: DriedPole[]) => {
      setProcessState((prev) => ({
        ...prev,
        driedPoles: [...poles, ...prev.driedPoles],
        trolleyBatches: prev.trolleyBatches.map((batch) =>
          batch.trolleyCode === trolleyCode
            ? {
                ...batch,
                status: "dried",
                ovenCode,
                driedAt: "Vừa tạo",
              }
            : batch,
        ),
      }));
    },
    [],
  );

  const handleCreateBaleDraft = useCallback((draft: BaleDraft) => {
    setProcessState((prev) => ({
      ...prev,
      baleDrafts: [draft, ...prev.baleDrafts],
    }));
  }, []);

  const handleUpdateBaleDraft = useCallback((draft: BaleDraft) => {
    setProcessState((prev) => ({
      ...prev,
      baleDrafts: prev.baleDrafts.map((item) =>
        item.draftCode === draft.draftCode ? draft : item,
      ),
    }));
  }, []);

  const stationItems: StationItem[] = selectedWorkflowPlan
    ? [
        {
          label: "1. Bồn → Mương",
          key: "tank",
          shortLabel: "1. Bồn",
          description: "Đổ mủ vào mương",
          stat: `${processState.troughBatches.length} mương đã nhận mủ`,
          children: (
            <TankStation
              workflowPlan={selectedWorkflowPlan}
              onCreateTroughBatches={handleCreateTroughBatches}
            />
          ),
        },
        {
          label: "2. Cắt tờ",
          key: "cutting",
          shortLabel: "2. Cắt",
          description: "Chọn mương, máy cắt, chất lượng",
          stat: `${processState.cutBatches.length} lô mương đã cắt`,
          children: (
            <TroughStation
              mode="cut"
              workflowPlan={selectedWorkflowPlan}
              troughBatches={processState.troughBatches}
              cutBatches={processState.cutBatches}
              rolledBatches={processState.rolledBatches}
              onCreateCutBatch={handleCreateCutBatch}
              onCreateRolledBatch={handleCreateRolledBatch}
            />
          ),
        },
        {
          label: "3. Cán tờ",
          key: "rolling",
          shortLabel: "3. Cán",
          description: "Cán theo từng luồng chất lượng",
          stat: `${processState.rolledBatches.length} lô mương đã cán`,
          children: (
            <TroughStation
              mode="roll"
              workflowPlan={selectedWorkflowPlan}
              troughBatches={processState.troughBatches}
              cutBatches={processState.cutBatches}
              rolledBatches={processState.rolledBatches}
              onCreateCutBatch={handleCreateCutBatch}
              onCreateRolledBatch={handleCreateRolledBatch}
            />
          ),
        },
        {
          label: "4. Treo sào",
          key: "trolley",
          shortLabel: "4. Sào",
          description: "Gắn sào lên xe gòong",
          stat: `${processState.trolleyBatches.reduce((sum, b) => sum + b.assignments.length, 0)} sào đã lên xe`,
          children: (
            <TrolleyStation
              workflowPlan={selectedWorkflowPlan}
              rolledBatches={processState.rolledBatches}
              trolleyBatches={processState.trolleyBatches}
              onCreateTrolleyBatch={handleCreateTrolleyBatch}
            />
          ),
        },
        {
          label: "5. Sấy khô",
          key: "drying",
          shortLabel: "5. Sấy",
          description: "Kéo xe vào lò, ghi nhận ra lò",
          stat: `${processState.driedPoles.length} sào đã sấy khô`,
          children: (
            <DryingStation
              workflowPlan={selectedWorkflowPlan}
              trolleyBatches={processState.trolleyBatches}
              driedPoles={processState.driedPoles}
              onMoveTrolleyToOven={handleMoveTrolleyToOven}
              onCreateDriedPoles={handleCreateDriedPoles}
            />
          ),
        },
        {
          label: "6. Ép bành",
          key: "baling",
          shortLabel: "6. Bành",
          description: "Ép bành và đóng pallet",
          stat: `${processState.baleDrafts.length} bành đã tạo/ép`,
          children: (
            <BalingStation
              workflowPlan={selectedWorkflowPlan}
              driedPoles={processState.driedPoles}
              baleDrafts={processState.baleDrafts}
              onCreateBaleDraft={handleCreateBaleDraft}
              onUpdateBaleDraft={handleUpdateBaleDraft}
            />
          ),
        },
      ]
    : [];

  const activeStation = stationItems.find((item) => item.key === activeTab);

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <div className="rss-processing-shell">
        {selectedWorkflowPlan ? (
          activeStation ? (
            <Space orientation="vertical" style={{ width: "100%" }} size="middle">
              <StationHeader
                station={activeStation}
                onBack={() => setActiveTab(null)}
              />
              {activeStation.children}
            </Space>
          ) : (
            <Space orientation="vertical" style={{ width: "100%" }} size="middle">
              <Flex align="flex-start" gap="middle" wrap>
                <Button
                  icon={<ArrowLeftOutlined />}
                  onClick={() => {
                    setSelectedWorkflowPlanId(null);
                    setActiveTab(null);
                  }}
                  style={{ marginTop: 6 }}>
                  Quay lại chọn lệnh
                </Button>
              </Flex>
              <ActiveWorkflowBanner plan={selectedWorkflowPlan} />
              <StationLauncher
                items={stationItems}
                onSelect={(key) => setActiveTab(key)}
              />
            </Space>
          )
        ) : (
          <>
            <Flex align="flex-start" gap="middle" wrap>
              <Button
                icon={<ArrowLeftOutlined />}
                onClick={() => {
                  if (onBack) {
                    onBack();
                    return;
                  }

                  router.back();
                }}
                style={{ marginTop: 6 }}>
                Quay lại
              </Button>
            </Flex>

            <ProductionWorkflowList
              plans={workflowPlans}
              selectedPlanId={undefined}
              isFetching={isFetchingOrders}
              onSelect={(plan) => setSelectedWorkflowPlanId(plan.id)}
            />
          </>
        )}
      </div>
    </Space>
  );
};

export default RssProcessing;

interface ProductionWorkflowListProps {
  plans: RssWorkflowPlan[];
  selectedPlanId?: string;
  isFetching: boolean;
  onSelect: (plan: RssWorkflowPlan) => void;
}

const ProductionWorkflowList = ({
  plans,
  selectedPlanId,
  isFetching,
  onSelect,
}: ProductionWorkflowListProps) => (
  <Card
    className="rss-workflow-list"
    size="small"
    title="Lệnh sản xuất"
    extra={<Tag color="blue">{plans.length}</Tag>}>
    <Spin spinning={isFetching}>
      <Row gutter={[12, 12]}>
        {plans.map((plan) => {
          const isActive = plan.id === selectedPlanId;

          return (
            <Col xs={24} md={12} xl={8} key={plan.id}>
              <button
                type="button"
                className={[
                  "rss-workflow-card",
                  isActive ? "rss-workflow-card-active" : "",
                ]
                  .filter(Boolean)
                  .join(" ")}
                aria-pressed={isActive}
                onClick={() => onSelect(plan)}>
                <Flex justify="space-between" align="flex-start" gap="small">
                  <Space orientation="vertical" size={0}>
                    <Text strong>{plan.orderCode}</Text>
                    <Text type="secondary">{plan.orderName}</Text>
                  </Space>

                  <Tag
                    color={getStatusColor(plan.status)}
                    style={{ margin: 0 }}>
                    {getStatusLabel(plan.status)}
                  </Tag>
                </Flex>

                <div className="rss-workflow-card-grid">
                  <div>
                    <Text type="secondary">Sản phẩm</Text>
                    <Text strong>{plan.productName}</Text>
                  </div>

                  <div>
                    <Text type="secondary">Kế hoạch</Text>
                    <Text strong>{formatKg(plan.requiredQuantityKg)} kg</Text>
                  </div>

                  <div>
                    <Text type="secondary">Mương</Text>
                    <Text strong>{plan.troughCodes.join(", ")}</Text>
                  </div>

                  <div>
                    <Text type="secondary">Sào</Text>
                    <Text strong>{plan.poleCodes.length} sào</Text>
                  </div>
                </div>

                <Flex gap="small" wrap>
                  <Tag color="blue">{plan.tankCode}</Tag>
                  <Tag color="cyan">{plan.cutEyeCodes.join(", ")}</Tag>
                  <Tag color="orange">{plan.trolleyCode}</Tag>
                  <Tag color="green">{plan.ovenCode}</Tag>
                </Flex>
              </button>
            </Col>
          );
        })}
      </Row>
    </Spin>
  </Card>
);

const ActiveWorkflowBanner = ({ plan }: { plan: RssWorkflowPlan }) => (
  <Card size="small" className="rss-active-workflow">
    <Flex justify="space-between" align="center" gap="small" wrap>
      <Space orientation="vertical" size={0}>
        <Text strong>{plan.orderCode}</Text>
        <Text type="secondary">{plan.orderName}</Text>
      </Space>
      <Tag color={getStatusColor(plan.status)} style={{ margin: 0 }}>
        {getStatusLabel(plan.status)}
      </Tag>
    </Flex>
    <Flex gap="small" wrap style={{ marginTop: 8 }}>
      <Tag color="blue">{plan.tankCode}</Tag>
      <Tag color="cyan">{plan.troughCodes.join(", ")}</Tag>
      <Tag color="orange">{plan.trolleyCode}</Tag>
      <Tag color="green">{plan.ovenCode}</Tag>
    </Flex>
  </Card>
);

const StationHeader = ({
  station,
  onBack,
}: {
  station: StationItem;
  onBack: () => void;
}) => (
  <Card size="small" className="rss-station-header">
    <Flex justify="space-between" align="center" gap="small" wrap>
      <Space orientation="vertical" size={0}>
        <Text strong>{station.label}</Text>
        <Text type="secondary">{station.description}</Text>
      </Space>
      <Button icon={<ArrowLeftOutlined />} onClick={onBack}>
        Đổi công đoạn
      </Button>
    </Flex>
  </Card>
);

const StationLauncher = ({
  items,
  onSelect,
}: {
  items: StationItem[];
  onSelect: (key: string) => void;
}) => (
  <Card size="small" className="rss-station-launcher" title="Chọn công đoạn">
    <div className="rss-station-launcher-grid">
      {items.map((item, index) => (
        <button
          key={item.key}
          type="button"
          className="rss-station-launcher-card"
          onClick={() => onSelect(item.key)}>
          <span className="rss-station-launcher-number">{index + 1}</span>
          <span className="rss-station-launcher-title">{item.label}</span>
          <span className="rss-station-launcher-desc">{item.description}</span>
          <span className="rss-station-launcher-stat">{item.stat}</span>
        </button>
      ))}
    </div>
  </Card>
);
