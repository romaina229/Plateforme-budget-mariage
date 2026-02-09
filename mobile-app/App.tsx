import AsyncStorage from '@react-native-async-storage/async-storage';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

type User = { id: number; username: string; fullname?: string };
type Category = { id: number; name: string };
type Expense = {
  id: number;
  name: string;
  category_id: number;
  category_name?: string;
  quantity: number;
  unit_price: number;
  frequency: number;
  paid: number;
};
type Stats = {
  grand_total: number;
  paid_total: number;
  unpaid_total: number;
  payment_percentage: number;
};
type PlannerTask = { id: string; title: string; done: boolean };
type Screen = 'dashboard' | 'expenses' | 'planner' | 'settings';

const DEFAULT_API_URL = 'http://10.0.2.2/Plateforme-budget-mariage';

export default function App() {
  const [apiBase, setApiBase] = useState(DEFAULT_API_URL);
  const [isBooting, setIsBooting] = useState(true);
  const [isBusy, setIsBusy] = useState(false);
  const [loggedIn, setLoggedIn] = useState(false);
  const [user, setUser] = useState<User | null>(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [activeScreen, setActiveScreen] = useState<Screen>('dashboard');

  const [stats, setStats] = useState<Stats | null>(null);
  const [expenses, setExpenses] = useState<Expense[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<number>(1);

  const [newExpense, setNewExpense] = useState({
    name: '',
    quantity: '1',
    unit_price: '',
    frequency: '1',
  });

  const [plannerTasks, setPlannerTasks] = useState<PlannerTask[]>([]);
  const [newTask, setNewTask] = useState('');

  const [weddingDate, setWeddingDate] = useState('');

  const totalByList = useMemo(
    () => expenses.reduce((sum, x) => sum + Number(x.quantity) * Number(x.unit_price) * Number(x.frequency), 0),
    [expenses],
  );

  useEffect(() => {
    void bootstrap();
  }, []);

  async function bootstrap() {
    try {
      const [savedApi, savedTasks] = await Promise.all([
        AsyncStorage.getItem('api_base_url'),
        AsyncStorage.getItem('planner_tasks'),
      ]);

      if (savedApi) setApiBase(savedApi);

      if (savedTasks) {
        setPlannerTasks(JSON.parse(savedTasks));
      } else {
        setPlannerTasks([
          { id: '1', title: 'Réserver la salle', done: false },
          { id: '2', title: 'Valider le traiteur', done: false },
          { id: '3', title: 'Signer le contrat photographe', done: false },
        ]);
      }

      await checkSession(savedApi || DEFAULT_API_URL);
    } finally {
      setIsBooting(false);
    }
  }

  async function request(path: string, options: RequestInit = {}, customBase?: string) {
    const base = customBase || apiBase;
    const res = await fetch(`${base}${path}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
      credentials: 'include',
    });

    let data: any = null;
    try {
      data = await res.json();
    } catch {
      throw new Error('Réponse API invalide (JSON attendu).');
    }

    if (!res.ok || data?.success === false) {
      throw new Error(data?.message || `Erreur API (${res.status})`);
    }

    return data;
  }

  async function checkSession(customBase?: string) {
    try {
      const data = await request('/api/auth_api.php?action=check', {}, customBase);
      if (data.logged_in) {
        setLoggedIn(true);
        setUser(data.user || null);
        await loadAllData(customBase);
      } else {
        setLoggedIn(false);
        setUser(null);
      }
    } catch {
      setLoggedIn(false);
      setUser(null);
    }
  }

  async function loadAllData(customBase?: string) {
    const [statsRes, expensesRes, categoriesRes] = await Promise.all([
      request('/api/api.php?action=get_stats', {}, customBase),
      request('/api/api.php?action=get_all', {}, customBase),
      request('/api/api.php?action=get_categories', {}, customBase),
    ]);

    setStats(statsRes.data || null);
    setExpenses(expensesRes.data || []);
    const cats: Category[] = categoriesRes.data || [];
    setCategories(cats);
    if (cats.length > 0) setSelectedCategory((old) => (cats.find((c) => c.id === old) ? old : cats[0].id));

    await loadWeddingDate(customBase);
  }

  async function loadWeddingDate(customBase?: string) {
    try {
      const data = await request('/api/api.php?action=get_wedding_date', {}, customBase);
      setWeddingDate(data.date || '');
    } catch {
      setWeddingDate('');
    }
  }

  async function onLogin() {
    if (!username.trim() || !password.trim()) {
      Alert.alert('Informations manquantes', 'Veuillez saisir le nom utilisateur et le mot de passe.');
      return;
    }

    try {
      setIsBusy(true);
      const data = await request('/api/auth_api.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ username: username.trim(), password }),
      });

      setLoggedIn(true);
      setUser(data.user || null);
      await loadAllData();
      setActiveScreen('dashboard');
      setPassword('');
    } catch (e) {
      Alert.alert('Connexion échouée', (e as Error).message);
    } finally {
      setIsBusy(false);
    }
  }

  async function onLogout() {
    try {
      setIsBusy(true);
      await request('/api/auth_api.php?action=logout');
    } catch {
      // no-op
    } finally {
      setLoggedIn(false);
      setUser(null);
      setIsBusy(false);
    }
  }

  async function onAddExpense() {
    if (!newExpense.name.trim() || !newExpense.unit_price.trim()) {
      Alert.alert('Validation', 'Le nom et le prix unitaire sont obligatoires.');
      return;
    }

    const quantity = Number(newExpense.quantity || '1');
    const unitPrice = Number(newExpense.unit_price);
    const frequency = Number(newExpense.frequency || '1');

    if (Number.isNaN(quantity) || Number.isNaN(unitPrice) || Number.isNaN(frequency)) {
      Alert.alert('Validation', 'Quantité, prix et fréquence doivent être numériques.');
      return;
    }

    try {
      setIsBusy(true);
      await request('/api/api.php?action=add', {
        method: 'POST',
        body: JSON.stringify({
          name: newExpense.name.trim(),
          quantity,
          unit_price: unitPrice,
          frequency,
          category_id: selectedCategory,
        }),
      });

      setNewExpense({ name: '', quantity: '1', unit_price: '', frequency: '1' });
      await loadAllData();
      Alert.alert('Succès', 'Dépense ajoutée.');
    } catch (e) {
      Alert.alert('Erreur ajout dépense', (e as Error).message);
    } finally {
      setIsBusy(false);
    }
  }

  async function onTogglePaid(id: number) {
    try {
      setIsBusy(true);
      await request(`/api/api.php?action=toggle_paid&id=${id}`);
      await loadAllData();
    } catch (e) {
      Alert.alert('Erreur', (e as Error).message);
    } finally {
      setIsBusy(false);
    }
  }

  async function onDeleteExpense(id: number) {
    Alert.alert('Confirmer', 'Supprimer cette dépense ?', [
      { text: 'Annuler', style: 'cancel' },
      {
        text: 'Supprimer',
        style: 'destructive',
        onPress: async () => {
          try {
            setIsBusy(true);
            await request(`/api/api.php?action=delete&id=${id}`);
            await loadAllData();
          } catch (e) {
            Alert.alert('Erreur', (e as Error).message);
          } finally {
            setIsBusy(false);
          }
        },
      },
    ]);
  }

  async function onSaveWeddingDate() {
    if (!weddingDate.trim()) {
      Alert.alert('Validation', 'Saisissez la date au format YYYY-MM-DD.');
      return;
    }

    try {
      setIsBusy(true);
      await request('/api/api.php?action=save_wedding_date', {
        method: 'POST',
        body: JSON.stringify({ date: weddingDate.trim() }),
      });
      Alert.alert('Succès', 'Date sauvegardée.');
    } catch (e) {
      Alert.alert('Erreur date', (e as Error).message);
    } finally {
      setIsBusy(false);
    }
  }

  async function saveTasks(next: PlannerTask[]) {
    setPlannerTasks(next);
    await AsyncStorage.setItem('planner_tasks', JSON.stringify(next));
  }

  async function addTask() {
    const title = newTask.trim();
    if (!title) return;
    const next = [{ id: `${Date.now()}`, title, done: false }, ...plannerTasks];
    setNewTask('');
    await saveTasks(next);
  }

  async function saveSettings() {
    const cleanUrl = apiBase.trim();
    if (!cleanUrl.startsWith('http')) {
      Alert.alert('URL invalide', 'L’URL API doit commencer par http:// ou https://');
      return;
    }

    try {
      setIsBusy(true);
      await AsyncStorage.setItem('api_base_url', cleanUrl);
      await checkSession(cleanUrl);
      Alert.alert('Succès', 'Paramètres enregistrés.');
    } catch (e) {
      Alert.alert('Erreur', (e as Error).message);
    } finally {
      setIsBusy(false);
    }
  }

  if (isBooting) {
    return (
      <SafeAreaView style={styles.centered}>
        <ActivityIndicator size="large" color="#8b4f8d" />
        <Text style={styles.muted}>Initialisation…</Text>
      </SafeAreaView>
    );
  }

  if (!loggedIn) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="dark" />
        <View style={styles.authCard}>
          <Text style={styles.title}>PJPM Mobile</Text>
          <Text style={styles.subtitle}>Planification, budget, dépenses et paiements</Text>

          <TextInput style={styles.input} value={username} onChangeText={setUsername} placeholder="Nom d'utilisateur" autoCapitalize="none" />
          <TextInput style={styles.input} value={password} onChangeText={setPassword} placeholder="Mot de passe" secureTextEntry />

          <TouchableOpacity style={styles.primaryButton} onPress={onLogin} disabled={isBusy}>
            <Text style={styles.buttonText}>{isBusy ? 'Connexion…' : 'Se connecter'}</Text>
          </TouchableOpacity>

          <Text style={styles.helpText}>Si la connexion échoue, vérifiez l’URL API dans les paramètres après première connexion locale.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />

      <View style={styles.topBar}>
        <Text style={styles.welcome}>Bonjour {user?.fullname || user?.username}</Text>
        <TouchableOpacity onPress={onLogout}>
          <Text style={styles.logout}>Déconnexion</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.navbar}>
        {([
          ['dashboard', 'Tableau'],
          ['expenses', 'Dépenses'],
          ['planner', 'Planning'],
          ['settings', 'Paramètres'],
        ] as [Screen, string][]).map(([screen, label]) => (
          <TouchableOpacity key={screen} onPress={() => setActiveScreen(screen)}>
            <Text style={[styles.navItem, activeScreen === screen && styles.navItemActive]}>{label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      {isBusy && <ActivityIndicator style={{ marginTop: 8 }} color="#8b4f8d" />}

      {activeScreen === 'dashboard' && (
        <ScrollView style={styles.section}>
          <View style={styles.statsGrid}>
            <Stat label="Budget total" value={`${(stats?.grand_total || totalByList).toFixed(2)} €`} />
            <Stat label="Déjà payé" value={`${(stats?.paid_total || 0).toFixed(2)} €`} />
            <Stat label="Reste à payer" value={`${(stats?.unpaid_total || 0).toFixed(2)} €`} />
            <Stat label="Progression" value={`${(stats?.payment_percentage || 0).toFixed(1)} %`} />
          </View>

          <Text style={styles.sectionTitle}>Date de mariage / évènement</Text>
          <TextInput
            style={styles.input}
            value={weddingDate}
            onChangeText={setWeddingDate}
            placeholder="YYYY-MM-DD"
            autoCapitalize="none"
          />
          <TouchableOpacity style={styles.primaryButton} onPress={onSaveWeddingDate}>
            <Text style={styles.buttonText}>Sauvegarder la date</Text>
          </TouchableOpacity>

          <Text style={styles.sectionTitle}>Dernières dépenses</Text>
          {expenses.slice(0, 5).map((expense) => (
            <View key={expense.id} style={styles.expenseCard}>
              <View style={{ flex: 1 }}>
                <Text style={styles.expenseName}>{expense.name}</Text>
                <Text style={styles.muted}>Total: {(expense.quantity * expense.unit_price * expense.frequency).toFixed(2)} €</Text>
              </View>
              <Text style={{ color: expense.paid ? '#2e7d32' : '#d84315' }}>{expense.paid ? 'Payé' : 'En attente'}</Text>
            </View>
          ))}
        </ScrollView>
      )}

      {activeScreen === 'expenses' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Nouvelle dépense</Text>
          <TextInput style={styles.input} placeholder="Nom" value={newExpense.name} onChangeText={(v) => setNewExpense((p) => ({ ...p, name: v }))} />

          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.catRow}>
            {categories.map((cat) => (
              <TouchableOpacity
                key={cat.id}
                style={[styles.catChip, selectedCategory === cat.id && styles.catChipActive]}
                onPress={() => setSelectedCategory(cat.id)}
              >
                <Text style={[styles.catLabel, selectedCategory === cat.id && styles.catLabelActive]}>{cat.name}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          <View style={styles.inlineInputs}>
            <TextInput style={[styles.input, styles.flex1]} placeholder="Qté" value={newExpense.quantity} onChangeText={(v) => setNewExpense((p) => ({ ...p, quantity: v }))} keyboardType="numeric" />
            <TextInput style={[styles.input, styles.flex2]} placeholder="Prix unitaire" value={newExpense.unit_price} onChangeText={(v) => setNewExpense((p) => ({ ...p, unit_price: v }))} keyboardType="numeric" />
            <TextInput style={[styles.input, styles.flex1]} placeholder="Fréq." value={newExpense.frequency} onChangeText={(v) => setNewExpense((p) => ({ ...p, frequency: v }))} keyboardType="numeric" />
          </View>

          <TouchableOpacity style={styles.primaryButton} onPress={onAddExpense}>
            <Text style={styles.buttonText}>Ajouter</Text>
          </TouchableOpacity>

          <FlatList
            data={expenses}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <View style={styles.expenseCard}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.expenseName}>{item.name}</Text>
                  <Text style={styles.muted}>{(item.quantity * item.unit_price * item.frequency).toFixed(2)} €</Text>
                </View>
                <Switch value={Boolean(item.paid)} onValueChange={() => onTogglePaid(item.id)} />
                <TouchableOpacity onPress={() => onDeleteExpense(item.id)}>
                  <Text style={styles.delete}>Supprimer</Text>
                </TouchableOpacity>
              </View>
            )}
          />
        </View>
      )}

      {activeScreen === 'planner' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Programmation de l’évènement</Text>

          <View style={styles.inlineInputs}>
            <TextInput
              style={[styles.input, styles.flex2]}
              placeholder="Nouvelle tâche"
              value={newTask}
              onChangeText={setNewTask}
            />
            <TouchableOpacity style={[styles.primaryButton, styles.flex1]} onPress={addTask}>
              <Text style={styles.buttonText}>Ajouter</Text>
            </TouchableOpacity>
          </View>

          <FlatList
            data={plannerTasks}
            keyExtractor={(item) => item.id}
            renderItem={({ item }) => (
              <View style={styles.taskRow}>
                <Text style={[styles.taskText, item.done && styles.done]}>{item.title}</Text>
                <Switch
                  value={item.done}
                  onValueChange={(value) =>
                    void saveTasks(plannerTasks.map((x) => (x.id === item.id ? { ...x, done: value } : x)))
                  }
                />
              </View>
            )}
          />
        </View>
      )}

      {activeScreen === 'settings' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Paramètres API</Text>
          <TextInput
            style={styles.input}
            value={apiBase}
            onChangeText={setApiBase}
            placeholder="URL de base API"
            autoCapitalize="none"
          />
          <TouchableOpacity style={styles.primaryButton} onPress={saveSettings}>
            <Text style={styles.buttonText}>Enregistrer</Text>
          </TouchableOpacity>
          <Text style={styles.helpText}>Exemple Android émulateur : http://10.0.2.2/Plateforme-budget-mariage</Text>
        </View>
      )}
    </SafeAreaView>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.statCard}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8f4fb' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 10 },

  topBar: {
    backgroundColor: '#fff',
    paddingHorizontal: 16,
    paddingVertical: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },

  authCard: { margin: 20, padding: 20, backgroundColor: '#fff', borderRadius: 14, gap: 12 },
  title: { fontSize: 30, fontWeight: '700', color: '#8b4f8d' },
  subtitle: { color: '#555' },

  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },

  primaryButton: {
    backgroundColor: '#8b4f8d',
    padding: 12,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonText: { color: '#fff', fontWeight: '700' },
  helpText: { color: '#666', fontSize: 12, marginTop: 8 },
  logout: { color: '#d84315', fontWeight: '700' },
  muted: { color: '#666' },

  navbar: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    paddingVertical: 12,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  navItem: { color: '#666' },
  navItemActive: { color: '#8b4f8d', fontWeight: '700' },

  section: { flex: 1, padding: 16 },
  welcome: { fontSize: 16, fontWeight: '600' },
  sectionTitle: { fontSize: 18, fontWeight: '700', marginTop: 6, marginBottom: 10, color: '#333' },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 12 },
  statCard: { width: '48%', backgroundColor: '#fff', padding: 12, borderRadius: 10 },
  statLabel: { color: '#666', marginBottom: 6 },
  statValue: { fontWeight: '700', color: '#8b4f8d' },

  expenseCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  expenseName: { fontWeight: '600' },
  delete: { color: '#d84315', fontWeight: '600' },

  catRow: { marginBottom: 10 },
  catChip: {
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#d8c2dc',
    marginRight: 8,
    backgroundColor: '#fff',
  },
  catChipActive: { backgroundColor: '#8b4f8d', borderColor: '#8b4f8d' },
  catLabel: { color: '#6d4a73' },
  catLabelActive: { color: '#fff', fontWeight: '700' },

  inlineInputs: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
  },
  flex1: { flex: 1 },
  flex2: { flex: 2 },

  taskRow: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  taskText: { fontSize: 15, flex: 1, marginRight: 12 },
  done: { textDecorationLine: 'line-through', color: '#2e7d32' },
});
