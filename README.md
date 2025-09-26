#  Laravel Folder Management System

A robust **folder and file management system** built with Laravel, designed for **efficient storage, organization, and manipulation of hierarchical data**.  
The system supports **folders and files with role-based permissions**, size calculations, searching, downloading as ZIP, unzipping, and more.  

It is **optimized for performance, scalability, and maintainability** with Redis caching, observers, and efficient database queries.

---

##  Key Features

- **Hierarchical Structure**  
  Folders and files organized in a tree structure using `parent_id` and `path` for fast navigation and querying.  

- **Permissions Management**  
  Role-based permissions (`read`, `write`, `delete`) per user on folders and descendants, stored in a dedicated `folder_permissions` table.  

- **Size Calculation**  
  Automatic recalculation of folder sizes based on descendant files, triggered via **observers**.  

- **File Operations**  
  Upload, download (as ZIP), unzip, and delete files/folders with permission checks.  

- **Searching and Querying**  
  Advanced searches: by path, duplicates, unique names, size ranges, and user associations.  

- **Queuing**  
  Asynchronous ZIP generation for large downloads using **Laravel Jobs**.  

- **Artisan Commands**  
  - `child:count` → Counts children for folders.  
  - `folder:order-by` → Orders folders by size, creation/update date, or children count.  
  - `folder:size` → Updates folder size based on descendants.  

- **Optimization**  
  Redis caching for queries, permissions, and computations → reduces DB load by **80–90%**.  

---

##  Database Schema

### **folders**
| Column     | Description |
|------------|-------------|
| `id`       | Primary key |
| `user_id`  | Foreign key to users (owner) |
| `parent_id`| Foreign key to folders (hierarchy) |
| `type`     | Enum (`folder`, `file`) |
| `name`     | Name of folder/file |
| `path`     | Materialized path (`1/2/3`) |
| `size`     | File size or sum of descendants |
| **Indexes**| `parent_id`, `path` |

### **folder_permissions**
| Column       | Description |
|--------------|-------------|
| `id`         | Primary key |
| `folder_id`  | Foreign key to folders |
| `user_id`    | Foreign key to users |
| `permission` | Enum (`read`, `write`, `delete`) |
| **Indexes**  | Composite (`user_id`, `folder_id`, `permission`) |

---

##  Core Components

### 1. **Models**
- **Folder Model**  
  - Relationships: `folders()`, `files()`, `children()`, `parent()`, `user()`, `permissions()`.  
  - `generatePath()` using Redis cache.  
  - Optimized with materialized paths (avoids recursion).  

- **FolderPermission Model**  
  - Simple Eloquent model for permissions.  

---

### 2. **Observers**
- **FolderObserver** (event-driven logic)  
  - Generates paths on create/update.  
  - Updates folder sizes & ancestor sizes.  
  - Updates descendant paths on parent change.  
  - Uses Redis for **caching paths and sizes**.  

---

### 3. **Controllers**
- **FolderController**  
  - CRUD for folders/files, permissions, upload, download, unzip, search.  
  - Optimized with Redis caching & eager loading.  

- **QueryController**  
  - Handles duplicates, unique names, size ranges, user associations.  
  - Redis caching for all query results.  

---

### 4. **Jobs**
- **GenerateZipArchive**  
  - Asynchronous ZIP creation for folder downloads.  
  - Uses DB queries instead of filesystem scanning.  
  - Redis caching for contents, job status, and ZIP path.  

---

### 5. **Artisan Commands**
- `child:count` → Counts children for folders.  
- `folder:order-by` → Orders folders by size, date, or children count.  
- `folder:size` → Updates folder size based on descendants.  

---
## ⚡ Performance Enhancements
- **Redis Integration**: Cache for paths, sizes, permissions, query results, job statuses.  
- **Query Optimization**: Indexed fields (`parent_id`, `path`), eager loading, minimized `LIKE`.  
- **Event-Driven**: Observers reduce controller logic.  
- **Security**: Permission checks everywhere + input validation.  
- **Scalability**: Asynchronous jobs, efficient tree operations with materialized paths.  
---

---
