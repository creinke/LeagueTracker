import React, {useState, useEffect} from 'react';
import {
    View,
    Text,
    StyleSheet,
    TouchableOpacity,
    FlatList,
    ActivityIndicator,
    Alert,
    Modal,
    TextInput,
    ScrollView,
} from 'react-native';
import {RouteProp, useNavigation, useRoute} from '@react-navigation/native';
import {StackNavigationProp} from '@react-navigation/stack';
import GameService from '../../services/GameService';

type RootStackParamList = {
    Substitution: { gameId: number };
};

type SubstitutionScreenRouteProp = RouteProp<RootStackParamList, 'Substitution'>;

interface PlayerRosterItem {
    id: number;
    name: string;
}

const SubstitutionScreen: React.FC = () => {
    const navigation = useNavigation<StackNavigationProp<any>>();
    const route = useRoute<SubstitutionScreenRouteProp>();
    const {gameId} = route.params;

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentGamePlayers, setCurrentGamePlayers] = useState<PlayerRosterItem[]>([]);
    const [leagueRoster, setLeagueRoster] = useState<PlayerRosterItem[]>([]);
    const [filteredRoster, setFilteredRoster] = useState<PlayerRosterItem[]>([]);
    const [selectedPlayers, setSelectedPlayers] = useState<number[]>([]);
    const [pickingForIndex, setPickingForIndex] = useState<number | null>(null);
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        fetchRoster();
    }, [gameId]);

    const fetchRoster = async () => {
        try {
            setLoading(true);
            const data = await GameService.getRoster(gameId);
            setCurrentGamePlayers(data.currentGamePlayers);
            setLeagueRoster(data.leagueRoster);
            setFilteredRoster(data.leagueRoster);
            setSelectedPlayers(data.currentGamePlayers.map(p => p.id));
        } catch (error) {
            console.error('Error fetching roster:', error);
            Alert.alert('Error', 'Failed to load player roster.');
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (text: string) => {
        setSearchQuery(text);
        if (text.trim() === '') {
            setFilteredRoster(leagueRoster);
        } else {
            const filtered = leagueRoster.filter(player =>
                player.name.toLowerCase().includes(text.toLowerCase())
            );
            setFilteredRoster(filtered);
        }
    };

    const openPicker = (index: number) => {
        setPickingForIndex(index);
        setSearchQuery('');
        setFilteredRoster(leagueRoster);
        setIsModalVisible(true);
    };

    const handleSelectPlayer = (playerId: number) => {
        if (pickingForIndex === null) return;

        const newSelected = [...selectedPlayers];
        newSelected[pickingForIndex] = playerId;
        setSelectedPlayers(newSelected);
        setIsModalVisible(false);
        setPickingForIndex(null);
    };

    const handleSave = async () => {
        try {
            setSaving(true);
            const result = await GameService.substitutePlayers(gameId, selectedPlayers);
            if (result.success) {
                // Automatically navigate back after successful substitution
                navigation.goBack();
            } else {
                Alert.alert('Error', result.message || 'Failed to substitute players.');
            }
        } catch (error) {
            console.error('Error substituting players:', error);
            Alert.alert('Error', 'Failed to substitute players.');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <View style={styles.centered}>
                <ActivityIndicator size="large" color="#007AFF"/>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <ScrollView style={styles.scrollContainer} contentContainerStyle={styles.scrollContent}>
                <Text style={styles.title}>Current Players in Game</Text>
                <View style={styles.currentPlayersContainer}>
                    {selectedPlayers.map((playerId, index) => {
                        const selectedPlayer = leagueRoster.find(p => p.id === playerId);

                        return (
                            <TouchableOpacity
                                key={index}
                                style={styles.playerSlot}
                                onPress={() => openPicker(index)}
                            >
                                <View style={styles.slotInfo}>
                                    <Text style={styles.playerName}>
                                        {selectedPlayer ? selectedPlayer.name : 'Unassigned'}
                                    </Text>
                                </View>
                                <Text style={styles.changeText}>[Tap to Change]</Text>
                            </TouchableOpacity>
                        );
                    })}
                </View>

                <View style={styles.footer}>
                    <Text style={styles.warning}>
                        Note: Saving substitutions will reset any scores entered for this game.
                    </Text>
                    <TouchableOpacity
                        style={[styles.saveButton, saving && styles.disabledButton]}
                        onPress={handleSave}
                        disabled={saving}
                    >
                        {saving ? (
                            <ActivityIndicator color="#fff"/>
                        ) : (
                            <Text style={styles.saveButtonText}>Save Substitutions</Text>
                        )}
                    </TouchableOpacity>
                </View>
            </ScrollView>

            <Modal
                visible={isModalVisible}
                animationType="slide"
                onRequestClose={() => setIsModalVisible(false)}
            >
                <View style={styles.modalContainer}>
                    <View style={styles.modalHeader}>
                        <Text style={styles.modalTitle}>Select Player</Text>
                        <TouchableOpacity onPress={() => setIsModalVisible(false)}>
                            <Text style={styles.closeText}>Cancel</Text>
                        </TouchableOpacity>
                    </View>

                    <TextInput
                        style={styles.searchInput}
                        placeholder="Search players..."
                        value={searchQuery}
                        onChangeText={handleSearch}
                    />

                    <FlatList
                        data={filteredRoster}
                        keyExtractor={(item) => item.id.toString()}
                        renderItem={({item}) => (
                            <TouchableOpacity
                                style={styles.rosterItem}
                                onPress={() => handleSelectPlayer(item.id)}
                            >
                                <Text style={styles.rosterItemText}>{item.name}</Text>
                                {selectedPlayers.includes(item.id) && (
                                    <Text style={styles.alreadySelected}>[In Game]</Text>
                                )}
                            </TouchableOpacity>
                        )}
                        ListEmptyComponent={
                            <Text style={styles.emptyText}>No players found.</Text>
                        }
                    />
                </View>
            </Modal>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff'},
    scrollContainer: {flex: 1},
    scrollContent: {padding: 16, paddingBottom: 40},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    title: {fontSize: 18, fontWeight: 'bold', marginBottom: 16},
    currentPlayersContainer: {marginBottom: 24},
    playerSlot: {
        padding: 12,
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 8,
        marginBottom: 10,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        backgroundColor: '#f9f9f9',
    },
    slotInfo: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
    },
    activeSlot: {borderColor: '#007AFF', backgroundColor: '#F0F8FF'},
    playerName: {flex: 1, fontSize: 16, fontWeight: '500'},
    changeText: {fontSize: 12, color: '#007AFF', fontWeight: 'bold', minWidth: 90, textAlign: 'right'},
    modalContainer: {flex: 1, padding: 20, backgroundColor: '#fff'},
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 20,
        paddingTop: 40,
    },
    modalTitle: {fontSize: 22, fontWeight: 'bold'},
    closeText: {fontSize: 16, color: '#007AFF'},
    searchInput: {
        height: 45,
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 8,
        paddingHorizontal: 12,
        marginBottom: 16,
        fontSize: 16,
    },
    rosterContainer: {flex: 1, borderTopWidth: 1, borderTopColor: '#eee', paddingTop: 16},
    rosterTitle: {fontSize: 16, fontWeight: 'bold', marginBottom: 8},
    rosterItem: {
        paddingVertical: 15,
        paddingHorizontal: 8,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    rosterItemText: {fontSize: 17},
    alreadySelected: {fontSize: 12, color: '#888', fontStyle: 'italic'},
    emptyText: {textAlign: 'center', marginTop: 30, color: '#888', fontSize: 16},
    footer: {marginTop: 20, paddingVertical: 16, borderTopWidth: 1, borderTopColor: '#eee'},
    warning: {color: '#FF3B30', fontSize: 12, textAlign: 'center', marginBottom: 16},
    saveButton: {
        backgroundColor: '#007AFF',
        padding: 16,
        borderRadius: 8,
        alignItems: 'center',
    },
    disabledButton: {backgroundColor: '#A0A0A0'},
    saveButtonText: {color: '#fff', fontSize: 18, fontWeight: 'bold'},
});

export default SubstitutionScreen;