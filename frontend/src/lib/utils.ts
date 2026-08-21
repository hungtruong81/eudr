import { siteConfig } from "@/lib/config";
import { type ClassValue, clsx } from "clsx";
import { Metadata } from "next";
import { twMerge } from "tailwind-merge";
import { CourseConfig, UserConfig } from "./types";
import dayjs from "dayjs";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function absoluteUrl(path: string) {
  return `${process.env.NEXT_PUBLIC_APP_URL || siteConfig.url}${path}`;
}

export function constructMetadata({
  title = siteConfig.name,
  description = siteConfig.description,
  image = absoluteUrl("/og"),
  ...props
}: {
  title?: string;
  description?: string;
  image?: string;
  [key: string]: Metadata[keyof Metadata];
}): Metadata {
  return {
    title: {
      template: "%s | " + siteConfig.name,
      default: siteConfig.name,
    },
    description: description || siteConfig.description,
    keywords: siteConfig.keywords,
    openGraph: {
      title,
      description,
      url: siteConfig.url,
      siteName: siteConfig.name,
      images: [
        {
          url: image,
          width: 1200,
          height: 630,
          alt: title,
        },
      ],
      type: "website",
      locale: "en_US",
    },
    icons: "/favicon.ico",
    metadataBase: new URL(siteConfig.url),
    authors: [
      {
        name: siteConfig.name,
        url: siteConfig.url,
      },
    ],
    ...props,
  };
}

export function formatDateVN(dateString: string) {
  return new Date(dateString).toLocaleDateString("vi-VN", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

export function formatDateDDMMYYYY(dateString: string) {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
}

export function formatDate(date: string) {
  let currentDate = new Date().getTime();
  if (typeof date !== "string" || !date) {
    return "";
  }
  if (!date.includes("T")) {
    date = `${date}T00:00:00`;
  }
  let targetDate = new Date(date).getTime();
  let timeDifference = Math.abs(currentDate - targetDate);
  let daysAgo = Math.floor(timeDifference / (1000 * 60 * 60 * 24));

  let fullDate = new Date(date).toLocaleString("en-us", {
    month: "long",
    day: "numeric",
    year: "numeric",
  });

  if (daysAgo < 1) {
    return "Today";
  } else if (daysAgo < 7) {
    return `${fullDate} (${daysAgo}d ago)`;
  } else if (daysAgo < 30) {
    const weeksAgo = Math.floor(daysAgo / 7);
    return `${fullDate} (${weeksAgo}w ago)`;
  } else if (daysAgo < 365) {
    const monthsAgo = Math.floor(daysAgo / 30);
    return `${fullDate} (${monthsAgo}mo ago)`;
  } else {
    const yearsAgo = Math.floor(daysAgo / 365);
    return `${fullDate} (${yearsAgo}y ago)`;
  }
}

export const generateBaseApiUrl = () => {
  // return process.env.NEXT_PUBLIC_URL_API;
  return "https://api-dev.sustainagri.vn";
};

export const formatUser = (data: any): UserConfig | null => {
  if (!data?.user_code) return null;

  return {
    userId: data.user_code,
    user_id: data.user_id,
    fullName: data.full_name,
    avatar: data.avatar,
    email: data.email,
    phone: data.phone,
    user_role: data.user_roles,
    permissions: data.permissions ?? [],
    company_name: data.company_name,
    company_short_name: data.company_short_name,
    accessToken: data.accessToken,
    register_type: data.register_type,
  };
};

export const formatCourse = (data: any) => {
  if (!data?.id_course) {
    return null;
  }
  const pathImage =
    data.file_path != ""
      ? process.env.NEXT_PUBLIC_CDN_URL + "/" + data.file_path
      : "/assets/images/placeholder.jpg";
  const metaPathImage =
    data.metadata?.image != ""
      ? process.env.NEXT_PUBLIC_CDN_URL + "/" + data.metadata.image
      : "/assets/images/placeholder.jpg";

  let obj = {
    courseId: data.id_course,
    slug: data.slug,
    name: data.name,
    filePath: pathImage,
    description: data.description,
    content: data.content,
    price: data.price,
    priceOld: data.price_old,
    totalChapters: data.total_chapters,
    isOnline: data.is_online > 0,
    metadata: {
      title: data.metadata?.title,
      description: data.metadata?.description,
      image: metaPathImage,
      publishedAt: data.metadata?.published_at,
    },
  };

  return obj as CourseConfig;
};

export function formatPrice(price: number): string {
  if (typeof price !== "number" || isNaN(price)) return "";
  // Ensure no decimal, force integer
  const intPrice = Math.floor(price);
  return intPrice
    .toLocaleString("vi-VN", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
    .replace(/,/g, ".");
}

export function formatVnCurrency(value: number | string): string {
  if (value === null || value === undefined || value === "") return "";

  const num =
    typeof value === "string" ? Number(value.replace(/\D/g, "")) : value;
  if (isNaN(num)) return "";

  return num.toLocaleString("vi-VN") + " VNĐ";
}

export function parseVnCurrency(value: string): number {
  if (!value) return 0;
  return Number(value.replace(/\D/g, "")) || 0;
}

export const getHighestRole = (roles?: { name: string }[] | string): string => {
  if (!roles) return "";
  if (typeof roles === "string") return roles;
  if (!Array.isArray(roles) || roles.length === 0) return "";

  const rolePriority: Record<string, number> = {
    company: 90,
    purchaser: 80,
    farmer: 50,
  };

  const sortedRoles = [...roles].sort((a, b) => {
    const priorityA = rolePriority[a.name] || 0;
    const priorityB = rolePriority[b.name] || 0;
    return priorityB - priorityA;
  });

  const highest = sortedRoles[0].name;
  return highest;
};

export function generateHarvestPlan(inputData: any) {
  const {
    plotId,
    startDate,
    endDate,
    pickupTime,
    latexWeight,
    scrapRubberWeight,
    frequency,
  } = inputData;

  const totalYield = latexWeight + scrapRubberWeight;
  let harvestDates = [];

  // Nếu không phải là Flexible, tự động tính toán các ngày
  if (frequency !== "FLEXIBLE") {
    let stepDays = 0;
    if (frequency === "D2") stepDays = 2;
    if (frequency === "D3") stepDays = 3;
    if (frequency === "D4") stepDays = 4;

    let currentDate = new Date(startDate);
    const finalDate = new Date(endDate);

    // Vòng lặp cộng ngày cho đến khi vượt quá End_Date
    while (currentDate <= finalDate) {
      harvestDates.push(new Date(currentDate));
      currentDate.setDate(currentDate.getDate() + stepDays);
    }
  } else {
    // Nếu là Flexible, tạo 1 dòng mặc định là ngày bắt đầu để user tự thêm bớt
    harvestDates.push(new Date(startDate));
  }

  const numberOfHarvests = harvestDates.length;
  // Chia đều sản lượng và làm tròn 2 chữ số thập phân
  const yieldPerHarvest =
    numberOfHarvests > 0 ? (totalYield / numberOfHarvests).toFixed(2) : 0;

  // Map ra cấu trúc mảng object cuối cùng
  const planTable = harvestDates.map((date) => {
    return {
      plot_id: plotId,
      pickup_date: dayjs(date).format("YYYY-MM-DD"),
      pickup_time: pickupTime,
      expected_yield: yieldPerHarvest,
    };
  });

  return planTable;
}
